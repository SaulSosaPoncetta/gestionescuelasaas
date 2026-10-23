<?php

namespace App\Services;

use App\Models\Correlativa;
use App\Models\Estudiante;
use App\Models\EstudianteCarrera;
use App\Models\Materia;
use App\Models\MesaExamen;
use App\Models\NotaCuatrimestre;
use App\Models\NotaFinal;
use Illuminate\Support\Collection;

class EstudianteFinalesService
{
    /**
     * Reglas de negocio (confirmadas):
     *
     * - No existe columna 'situacion' en notas_cuatrimestre: la condición de
     *   cursada se deriva comparando el 'valor' contra materias.nota_aprobada
     *   y materias.nota_promocionable.
     *      valor >= nota_promocionable  -> promocionó (no rinde final)
     *      valor >= nota_aprobada       -> regularizó (puede rendir el final)
     *      valor <  nota_aprobada       -> no aprobó la cursada (bloqueada)
     *      sin nota                     -> no cursó (bloqueada)
     *
     * - El final se aprueba con nota >= materias.nota_aprobada. Si nunca lo
     *   rindió, no está aprobado.
     *
     * - Si promocionó, la nota de cierre de cursada (notas_cuatrimestre)
     *   also cuenta como "final aprobado" para efectos de correlativas Y
     *   aparece en la pestaña "Aprobado" de la pantalla Finales (modalidad
     *   "Promoción"), sin que exista fila en notas_final.
     *
     * - "Bajas" = fila en notas_final con valor null cuya mesa quedó
     *   deshabilitada (mesas_examen.habilitado = false) después de la
     *   inscripción.
     */

    // ── Situación de cursada ──────────────────────────────────────────

    /**
     * @return object{estado: string, valor: ?float, nota_cuatrimestre: ?NotaCuatrimestre}
     * estado: 'promocionado' | 'regular' | 'no_aprobada' | 'sin_cursar'
     */
    public function situacionCursada(int $idUsuario, Materia $materia): object
    {
        $nota = NotaCuatrimestre::where('id_usuario', $idUsuario)
            ->where('id_materia', $materia->id_materia)
            ->orderByDesc('fecha_carga')
            ->first();

        if (!$nota) {
            return (object) ['estado' => 'sin_cursar', 'valor' => null, 'nota_cuatrimestre' => null];
        }

        $valor = (float) $nota->valor;

        if ($materia->nota_promocionable !== null && $valor >= (float) $materia->nota_promocionable) {
            $estado = 'promocionado';
        } elseif ($valor >= (float) $materia->nota_aprobada) {
            $estado = 'regular';
        } else {
            $estado = 'no_aprobada';
        }

        return (object) ['estado' => $estado, 'valor' => $valor, 'nota_cuatrimestre' => $nota];
    }

    /**
     * ¿Tiene el final de esta materia aprobado (ya sea rindiéndolo o por
     * promoción de la cursada)?
     */
    public function finalAprobado(int $idUsuario, Materia $materia): bool
    {
        if ($this->situacionCursada($idUsuario, $materia)->estado === 'promocionado') {
            return true;
        }

        return NotaFinal::where('id_usuario', $idUsuario)
            ->where('id_materia', $materia->id_materia)
            ->where('valor', '>=', $materia->nota_aprobada)
            ->exists();
    }

    // ── Correlativas ───────────────────────────────────────────────────

    /**
     * ¿Cumple el estudiante todas las correlativas para RENDIR el final de
     * esta materia? (no confundir con las correlativas para cursar).
     */
    public function correlativasFinalCumplidas(int $idUsuario, Materia $materia): bool
    {
        return Correlativa::where('id_materia', $materia->id_materia)
            ->with('materiaCorrelativa')
            ->get()
            ->every(function (Correlativa $correlativa) use ($idUsuario) {
                $materiaCorrelativa = $correlativa->materiaCorrelativa;

                if (!$materiaCorrelativa) {
                    return true; // dato inconsistente, no bloqueamos por esto
                }

                if ($correlativa->correlativa_cursada) {
                    $situacion = $this->situacionCursada($idUsuario, $materiaCorrelativa)->estado;
                    if (!in_array($situacion, ['regular', 'promocionado'], true)) {
                        return false;
                    }
                }

                if ($correlativa->correlativa_final) {
                    if (!$this->finalAprobado($idUsuario, $materiaCorrelativa)) {
                        return false;
                    }
                }

                return true;
            });
    }

    /**
     * Nombres de las correlativas que faltan para rendir el final, para
     * mostrar un motivo legible en vez de solo "no cumplís correlativas".
     */
    protected function correlativasFaltantes(int $idUsuario, Materia $materia): Collection
    {
        return Correlativa::where('id_materia', $materia->id_materia)
            ->with('materiaCorrelativa')
            ->get()
            ->filter(function (Correlativa $correlativa) use ($idUsuario) {
                $materiaCorrelativa = $correlativa->materiaCorrelativa;

                if (!$materiaCorrelativa) {
                    return false;
                }

                if ($correlativa->correlativa_cursada) {
                    $situacion = $this->situacionCursada($idUsuario, $materiaCorrelativa)->estado;
                    if (!in_array($situacion, ['regular', 'promocionado'], true)) {
                        return true;
                    }
                }

                if ($correlativa->correlativa_final) {
                    if (!$this->finalAprobado($idUsuario, $materiaCorrelativa)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(fn (Correlativa $c) => $c->materiaCorrelativa->nombre)
            ->values();
    }

    // ── Pantalla "Finales" (historial del alumno) ───────────────────────

    /**
     * Devuelve, para una carrera del estudiante, una fila por cada intento
     * de final (uno por mesa rendida/inscripta) + una fila sintética por
     * cada materia promocionada, clasificadas en las 4 pestañas.
     *
     * @return array{inscripto: Collection, aprobado: Collection, no_aprobado: Collection, bajas: Collection}
     */
    public function finalesDelEstudiante(int $idUsuario, int $idCarrera): array
    {
        $materias = Materia::where('id_carrera', $idCarrera)->get()->keyBy('id_materia');

        $filas = collect();

        // Filas sintéticas por promoción (no hay mesa/notas_final de por medio)
        foreach ($materias as $materia) {
            $situacion = $this->situacionCursada($idUsuario, $materia);

            if ($situacion->estado === 'promocionado') {
                $filas->push((object) [
                    'tab'        => 'aprobado',
                    'fecha_hora' => optional($situacion->nota_cuatrimestre->fecha_carga)->format('d/m/Y'),
                    'espacio'    => $materia->nombre,
                    'anio'       => $materia->anio,
                    'modalidad'  => 'Promoción',
                    'nota'       => number_format($situacion->valor, 2),
                ]);
            }
        }

        // Filas por cada intento real en notas_final
        NotaFinal::where('id_usuario', $idUsuario)
            ->whereIn('id_materia', $materias->keys())
            ->with(['mesaExamen.tipoMesa'])
            ->orderByDesc('fecha_carga')
            ->get()
            ->each(function (NotaFinal $notaFinal) use ($materias, $filas) {
                $materia = $materias->get($notaFinal->id_materia);
                $mesa = $notaFinal->mesaExamen;

                if (!$materia) {
                    return;
                }

                if ($notaFinal->valor !== null) {
                    $tab = ((float) $notaFinal->valor >= (float) $materia->nota_aprobada)
                        ? 'aprobado'
                        : 'no_aprobado';
                } elseif ($notaFinal->baja || ($mesa && !$mesa->habilitado)) {
                    $tab = 'bajas';
                } else {
                    $tab = 'inscripto';
                }

                $filas->push((object) [
                    'tab'        => $tab,
                    'id_mesa'    => $notaFinal->id_mesa,
                    'fecha_hora' => optional($mesa?->fecha)->format('d/m/Y H:i') . ($mesa ? ' hs' : ''),
                    'espacio'    => $materia->nombre,
                    'anio'       => $materia->anio,
                    'modalidad'  => $mesa?->tipoMesa->descripcion ?? '',
                    'nota'       => $notaFinal->valor !== null ? number_format($notaFinal->valor, 2) : '—',
                ]);
            });

        return [
            'inscripto'   => $filas->where('tab', 'inscripto')->values(),
            'aprobado'    => $filas->where('tab', 'aprobado')->values(),
            'no_aprobado' => $filas->where('tab', 'no_aprobado')->values(),
            'bajas'       => $filas->where('tab', 'bajas')->values(),
        ];
    }

    // ── Pantalla "Inscripción a finales" ────────────────────────────────

    /**
     * Para cada materia de la carrera ACTIVA del estudiante, resuelve si
     * puede inscribirse a rendir el final y, si puede, qué mesas (fechas)
     * tiene disponibles. Agrupado por año, como espera la vista.
     *
     * @return Collection<int, Collection> ['anio' => Collection de filas]
     */
    public function materiasParaInscripcion(int $idUsuario, int $idCarrera): Collection
    {
        $materias = Materia::where('id_carrera', $idCarrera)
            ->orderBy('anio')
            ->orderBy('nombre')
            ->get();

        return $materias
            ->map(function (Materia $materia) use ($idUsuario) {
                return $this->resolverFilaInscripcion($idUsuario, $materia);
            })
            ->groupBy('anio');
    }

    /**
     * Arma la fila "bloqueada" con un motivo legible, para que la vista
     * pueda mostrar por qué no hay fechas disponibles en vez de dejar la
     * columna vacía sin explicación.
     */
    protected function filaBloqueada(Materia $materia, string $condicion, string $motivo): object
    {
        return (object) [
            'nombre'    => $materia->nombre,
            'anio'      => $materia->anio,
            'condicion' => $condicion,
            'bloqueada' => true,
            'motivo'    => $motivo,
            'fechas'    => collect(),
        ];
    }

    protected function resolverFilaInscripcion(int $idUsuario, Materia $materia): object
    {
        $condicion = $this->condicionCursada($idUsuario, $materia);

        $situacion = $this->situacionCursada($idUsuario, $materia);

        // Ya promocionó: no rinde final, no tiene sentido ofrecerle fechas.
        if ($situacion->estado === 'promocionado') {
            return $this->filaBloqueada($materia, $condicion, 'Promocionaste la cursada: no rendís final.');
        }

        // No regularizó la cursada: bloqueada.
        if ($situacion->estado !== 'regular') {
            $motivo = $situacion->estado === 'sin_cursar'
                ? 'Todavía no cursaste esta materia.'
                : 'No aprobaste la cursada.';
            return $this->filaBloqueada($materia, $condicion, $motivo);
        }

        // Ya tiene el final aprobado: bloqueada (nada que rendir).
        if ($this->finalAprobado($idUsuario, $materia)) {
            return $this->filaBloqueada($materia, $condicion, 'Ya tenés el final aprobado.');
        }

        // Ya está inscripto a una mesa pendiente de nota: bloqueada (no se
        // reinscribe hasta tener resultado).
        $yaInscripto = NotaFinal::where('id_usuario', $idUsuario)
            ->where('id_materia', $materia->id_materia)
            ->whereNull('valor')
            ->whereHas('mesaExamen', fn ($q) => $q->where('habilitado', true))
            ->exists();

        if ($yaInscripto) {
            return $this->filaBloqueada(
                $materia,
                $condicion,
                'Ya estás inscripto a una mesa de esta materia (ver pestaña "Inscripto" en Finales).'
            );
        }

        // No cumple correlativas para rendir: bloqueada.
        if (!$this->correlativasFinalCumplidas($idUsuario, $materia)) {
            $faltantes = $this->correlativasFaltantes($idUsuario, $materia);
            $motivo = $faltantes->isNotEmpty()
                ? 'Te falta aprobar: ' . $faltantes->implode(', ') . '.'
                : 'No cumplís las correlativas para rendir.';
            return $this->filaBloqueada($materia, $condicion, $motivo);
        }

        // Habilitada: buscamos mesas futuras y habilitadas para ofrecer fecha.
        $mesas = MesaExamen::where('id_materia', $materia->id_materia)
            ->where('habilitado', true)
            ->where('fecha', '>', now())
            ->orderBy('fecha')
            ->get(['id_mesa', 'fecha']);

        if ($mesas->isEmpty()) {
            return $this->filaBloqueada($materia, $condicion, 'No hay fechas de examen cargadas todavía.');
        }

        return (object) [
            'nombre'    => $materia->nombre,
            'anio'      => $materia->anio,
            'condicion' => $condicion,
            'bloqueada' => false,
            'motivo'    => null,
            'fechas'    => $mesas->map(fn (MesaExamen $m) => (object) [
                'id_mesa' => $m->id_mesa,
                'fecha'   => $m->fecha->format('d/m/y H:i'),
            ]),
        ];
    }

    /**
     * "Regular" / "Libre" según la última inscripción a la cursada de esa
     * materia.
     */
    protected function condicionCursada(int $idUsuario, Materia $materia): string
    {
        $inscripcion = \DB::table('inscripciones_materia')
            ->join('tipos_estudiante', 'tipos_estudiante.id_tipo_estudiante', '=', 'inscripciones_materia.id_tipo_estudiante')
            ->where('inscripciones_materia.id_usuario', $idUsuario)
            ->where('inscripciones_materia.id_materia', $materia->id_materia)
            ->orderByDesc('inscripciones_materia.id_ciclo')
            ->first(['tipos_estudiante.descripcion_tipo']);

        return $inscripcion->descripcion_tipo ?? '';
    }

    // ── Guardar inscripción ──────────────────────────────────────────

    /**
     * Inscribe al estudiante a las mesas elegidas (una por materia, como
     * mucho). Revalida todo server-side: no confía en que el front solo
     * haya podido mandar mesas habilitadas.
     *
     * @param int[] $idsMesa
     * @return array{inscriptas: int[], rechazadas: array<int, string>} ids de mesa insc./motivo por las que se rechazó cada una
     */
    public function inscribir(int $idUsuario, array $idsMesa): array
    {
        $inscriptas = [];
        $rechazadas = [];

        foreach ($idsMesa as $idMesa) {
            $mesa = MesaExamen::with('materia')->find($idMesa);

            if (!$mesa || !$mesa->habilitado || $mesa->fecha->isPast()) {
                $rechazadas[$idMesa] = 'La mesa no existe o ya no está disponible.';
                continue;
            }

            $fila = $this->resolverFilaInscripcion($idUsuario, $mesa->materia);

            $mesaHabilitadaParaEsteAlumno = $fila->fechas->contains('id_mesa', $mesa->id_mesa);

            if (!$mesaHabilitadaParaEsteAlumno) {
                $rechazadas[$idMesa] = 'No cumplís las condiciones para inscribirte a ' . $mesa->materia->nombre . '.';
                continue;
            }

            NotaFinal::firstOrCreate(
                [
                    'id_mesa'    => $mesa->id_mesa,
                    'id_usuario' => $idUsuario,
                ],
                [
                    'id_materia'  => $mesa->id_materia,
                    'valor'       => null,
                    'fecha_carga' => now(),
                ]
            );

            $inscriptas[] = $idMesa;
        }

        return ['inscriptas' => $inscriptas, 'rechazadas' => $rechazadas];
    }

    // ── Helper de carrera activa ─────────────────────────────────────

    public function carreraActiva(int $idUsuario): ?EstudianteCarrera
    {
        return EstudianteCarrera::with('carrera')
            ->where('id_usuario', $idUsuario)
            ->where('id_estado_carrera', 4) // 'Activa'
            ->first();
    }

    public function carrerasDelEstudiante(int $idUsuario): Collection
    {
        return EstudianteCarrera::with('carrera')
            ->where('id_usuario', $idUsuario)
            ->get()
            ->pluck('carrera')
            ->filter()
            ->unique('id_carrera')
            ->values();
    }

    /**
     * Da de baja al estudiante de una mesa a la que está inscripto y todavía
     * no rindió (valor null). No toca mesas_examen.habilitado porque esa
     * mesa puede tener otros alumnos inscriptos: solo marca su propia fila.
     */
    public function darDeBaja(int $idUsuario, int $idMesa): bool
    {
        $notaFinal = NotaFinal::where('id_usuario', $idUsuario)
            ->where('id_mesa', $idMesa)
            ->whereNull('valor')
            ->where('baja', false)
            ->first();

        if (!$notaFinal) {
            return false;
        }

        $notaFinal->baja = true;
        $notaFinal->save();

        return true;
    }
}