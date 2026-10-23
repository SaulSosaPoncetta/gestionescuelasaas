<?php

namespace App\Services;

use App\Models\CicloLectivo;
use App\Models\EstadoEstudiante;
use App\Models\Estudiante;
use App\Models\InscripcionMateria;
use App\Models\Materia;
use App\Models\NotaCuatrimestre;
use App\Models\TipoEstudiante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CursadaEstService
{
    /**
     * Mapeo de "pestaña/tabla" del front (data-tabla) => posibles valores del
     * campo notas_cuatrimestre.situacion.
     *
     * SUPUESTO: no tengo el listado real de valores que toma `situacion`
     * (no viene seedeado en la factory). Ajustar estas cadenas contra los
     * valores reales de la base ("Cursando", "Regular", "Promocionado",
     * "Desaprobado", "Libre", etc).
     */
    protected const SITUACIONES_POR_TABLA = [
        'inscripto'   => ['cursando', 'inscripto', 'regular'],
        'aprobado'    => ['aprobado', 'promocionado'],
        'no-aprobado' => ['desaprobado', 'libre', 'no aprobado'],
    ];

    /**
     * Situaciones que dejan a una materia "resuelta" a los fines de la
     * matriculación: si el alumno ya está en alguno de estos estados para
     * una materia, NO tiene que (ni puede) volver a matricularse a ella.
     *
     * - aprobado/promocionado: ya la aprobó, punto.
     * - regular: ya regularizó la cursada (aprobó la parte de cursada) y
     *   solo le queda rendir el final — no vuelve a cursarla, así que
     *   tampoco debe reaparecer como "disponible" en matriculación, ni caer
     *   en "Libre" si no se matricula (por eso está en esta lista y no la
     *   tratamos igual que "cursando").
     *
     * SUPUESTO: mismo disclaimer que SITUACIONES_POR_TABLA — ajustar contra
     * los valores reales de `situacion` en la base.
     */
    protected const SITUACIONES_RESUELTAS_SIN_MATRICULAR = ['aprobado', 'promocionado', 'regular'];

    /**
     * TODO: "Tramos/deportes" (data-tabla="bajas") todavía no está resuelto.
     * Sospecho que no depende de `situacion` sino de un TipoMateria especial
     * (ej: id_tipo_materia de "Tramo" o "Deporte"). Falta el modelo TipoMateria
     * para confirmarlo — por ahora esta pestaña queda siempre vacía.
     */

    /**
     * Arma toda la data necesaria para la vista Cursada:
     * - Carreras del estudiante (para el <select>)
     * - Inscripciones agrupadas por carrera y por tabla (inscripto/aprobado/no-aprobado/bajas)
     */
    public function obtenerDatosCursada(Estudiante $estudiante): array
    {
        $carreras = $estudiante->carreras;

        $inscripciones = InscripcionMateria::query()
            ->with(['materia.carrera', 'cicloLectivo'])
            ->where('id_usuario', $estudiante->id_usuario)
            ->get();

        // Notas cuatrimestrales del estudiante, indexadas por "id_materia-id_ciclo"
        // para poder machear cada inscripción con su situación sin hacer N+1.
        $notas = NotaCuatrimestre::query()
            ->where('id_usuario', $estudiante->id_usuario)
            ->get()
            ->keyBy(fn (NotaCuatrimestre $nota) => $nota->id_materia . '-' . $nota->id_ciclo);

        $tablas = $this->agruparPorCarreraYTabla($inscripciones, $notas, $carreras);

        return [
            'carreras' => $carreras,
            'tablas'   => $tablas,
        ];
    }

    /**
     * Devuelve un array con la forma:
     * [
     *   $id_carrera => [
     *      'inscripto'   => Collection<row>,
     *      'aprobado'    => Collection<row>,
     *      'no-aprobado' => Collection<row>,
     *      'bajas'       => Collection<row>,
     *   ],
     *   ...
     * ]
     */
    protected function agruparPorCarreraYTabla(Collection $inscripciones, Collection $notas, Collection $carreras): array
    {
        $resultado = [];

        // Inicializamos todas las combinaciones carrera/tabla para que la vista
        // siempre tenga una Collection (vacía o no) y no falle con índices inexistentes.
        foreach ($carreras as $carrera) {
            foreach (['inscripto', 'aprobado', 'no-aprobado', 'bajas'] as $tabla) {
                $resultado[$carrera->id_carrera][$tabla] = collect();
            }
        }

        foreach ($inscripciones as $inscripcion) {
            $materia = $inscripcion->materia;

            if (!$materia) {
                continue;
            }

            $idCarrera = $materia->id_carrera;
            $nota = $notas->get($inscripcion->id_materia . '-' . $inscripcion->id_ciclo);
            $tabla = $this->resolverTabla($nota);

            $resultado[$idCarrera][$tabla] ??= collect();

            $resultado[$idCarrera][$tabla]->push([
                'espacio_curricular' => $materia->nombre,
                'anio'               => $materia->anio,
                'modalidad'          => $materia->acreditacion, // "Regular, Promocion" / "Promocion directa"
                'situacion'          => $nota?->situacion,
                'id_materia'         => $materia->id_materia,
                'id_ciclo'           => $inscripcion->id_ciclo,
            ]);
        }

        return $resultado;
    }

    /**
     * Decide en qué pestaña cae una inscripción, en base a
     * notas_cuatrimestre.situacion. Si todavía no hay nota cargada,
     * se considera "inscripto" (cursando, sin resultado aún).
     */
    protected function resolverTabla(?NotaCuatrimestre $nota): string
    {
        if (!$nota || !$nota->situacion) {
            return 'inscripto';
        }

        foreach (self::SITUACIONES_POR_TABLA as $tabla => $posibles) {
            if ($this->situacionCoincide($nota->situacion, $posibles)) {
                return $tabla;
            }
        }

        return 'inscripto';
    }

    /**
     * True si `$situacion` (texto libre de notas_cuatrimestre.situacion)
     * coincide con alguna de las cadenas de `$posibles`. Centraliza la
     * comparación case-insensitive/"contains" que se repetía en varios lados.
     */
    protected function situacionCoincide(?string $situacion, array $posibles): bool
    {
        if (!$situacion) {
            return false;
        }

        $situacion = strtolower($situacion);

        foreach ($posibles as $posible) {
            if (str_contains($situacion, $posible)) {
                return true;
            }
        }

        return false;
    }

    // ────────────────────────────────────────────────────────────────
    // Matriculación a materias
    // ────────────────────────────────────────────────────────────────

    /**
     * Arma la data para la vista de matriculación (ciclo vigente):
     * - Materias "disponibles": ver materiasDisponiblesParaCiclo().
     * - Materias "matriculadas": ya inscripto en el ciclo vigente.
     *
     * Si no hay ningún ciclo con matriculación abierta hoy, devuelve todo vacío
     * y 'ciclo' => null (la vista puede usar esto para mostrar un aviso).
     */
    public function obtenerDatosMatriculacion(Estudiante $estudiante): array
    {
        $ciclo = $this->obtenerCicloConMatriculacionAbierta();

        if (!$ciclo) {
            return [
                'ciclo'        => null,
                'disponibles'  => collect(),
                'matriculadas' => collect(),
            ];
        }

        $disponibles = $this->materiasDisponiblesParaCiclo($estudiante, $ciclo)
            ->map(fn (Materia $materia) => $this->filaMateria($materia));

        $idsInscriptoEnCiclo = InscripcionMateria::query()
            ->where('id_usuario', $estudiante->id_usuario)
            ->where('id_ciclo', $ciclo->id_ciclo)
            ->pluck('id_materia');

        $matriculadas = Materia::query()
            ->whereIn('id_materia', $idsInscriptoEnCiclo)
            ->get()
            ->map(fn (Materia $materia) => $this->filaMateria($materia));

        return [
            'ciclo'        => $ciclo,
            'disponibles'  => $disponibles,
            'matriculadas' => $matriculadas,
        ];
    }

    /**
     * Fila "plana" de una materia para las vistas de matriculación.
     */
    protected function filaMateria(Materia $materia): array
    {
        return [
            'id_materia'         => $materia->id_materia,
            'espacio_curricular' => $materia->nombre,
            'anio'               => $materia->anio,
            'modalidad'          => $materia->acreditacion,
            // TODO/SUPUESTO: no encontré de dónde sale "Profesor" en el modelo
            // Materia que me pasaste. Ajustar según la relación real (¿Materia
            // -> Docente/Profesor?).
            'profesor'           => $materia->profesor ?? null,
        ];
    }

    /**
     * Materias que el estudiante PODRÍA matricular para un ciclo dado: son
     * de una de sus carreras, cumplen correlativas de cursada, no están ya
     * resueltas (aprobadas/regularizadas) ni cursando activamente en otro
     * ciclo, y no están ya inscriptas en ESTE ciclo.
     *
     * Se usa tanto para la vista en vivo (ciclo vigente) como para el
     * proceso de cierre de matriculación (cerrarMatriculacionCiclo), que la
     * llama con un ciclo ya vencido para saber qué "se le pasó" al alumno.
     *
     * @return Collection<int, Materia>
     */
    protected function materiasDisponiblesParaCiclo(Estudiante $estudiante, CicloLectivo $ciclo): Collection
    {
        $idsCarreras = $estudiante->carreras->pluck('id_carrera');

        $materias = Materia::query()
            ->whereIn('id_carrera', $idsCarreras)
            ->with(['carrera', 'correlativas.materiaCorrelativa'])
            ->get();

        $notas = $this->notasVigentesPorMateria($estudiante);

        $idsInscriptoEnCiclo = InscripcionMateria::query()
            ->where('id_usuario', $estudiante->id_usuario)
            ->where('id_ciclo', $ciclo->id_ciclo)
            ->pluck('id_materia')
            ->all();

        // Materias con una inscripción activa (sin nota todavía) en
        // CUALQUIER ciclo: las está cursando en este momento en otro lado,
        // no tiene sentido ofrecérselas de nuevo.
        $idsMateriasCursandoActualmente = InscripcionMateria::query()
            ->where('id_usuario', $estudiante->id_usuario)
            ->pluck('id_materia')
            ->filter(function ($idMateria) use ($notas) {
                $nota = $notas->get($idMateria);
                return !$nota || !$nota->situacion;
            })
            ->all();

        return $materias->filter(function (Materia $materia) use (
            $idsInscriptoEnCiclo,
            $idsMateriasCursandoActualmente,
            $notas
        ) {
            if (in_array($materia->id_materia, $idsInscriptoEnCiclo, true)) {
                return false;
            }

            if (in_array($materia->id_materia, $idsMateriasCursandoActualmente, true)) {
                return false;
            }

            $notaPropia = $notas->get($materia->id_materia);
            if ($this->situacionCoincide($notaPropia?->situacion, self::SITUACIONES_RESUELTAS_SIN_MATRICULAR)) {
                return false;
            }

            return $this->cumpleCorrelativas($materia, $notas);
        })->values();
    }

    /**
     * Inscribe al estudiante en las materias indicadas, para el ciclo lectivo
     * cuya matriculación está abierta hoy. Vuelve a validar todo del lado del
     * servidor (no confiar en lo que mandó el front).
     *
     * @param  array<int>  $idsMaterias
     * @return Collection<int, InscripcionMateria>
     *
     * @throws ValidationException
     */
    public function matricular(Estudiante $estudiante, array $idsMaterias): Collection
    {
        $ciclo = $this->obtenerCicloConMatriculacionAbierta();

        if (!$ciclo) {
            throw ValidationException::withMessages([
                'ciclo' => 'No hay un ciclo lectivo con período de matriculación abierto en este momento.',
            ]);
        }

        if (empty($idsMaterias)) {
            throw ValidationException::withMessages([
                'materias' => 'No se seleccionó ninguna materia para matricular.',
            ]);
        }

        $idsCarreras = $estudiante->carreras->pluck('id_carrera');

        $materias = Materia::query()
            ->whereIn('id_materia', $idsMaterias)
            ->whereIn('id_carrera', $idsCarreras)
            ->with('correlativas.materiaCorrelativa')
            ->get()
            ->keyBy('id_materia');

        // Pidieron matricular algo que no existe o no es de una carrera del estudiante.
        $faltantes = collect($idsMaterias)->diff($materias->keys());
        if ($faltantes->isNotEmpty()) {
            throw ValidationException::withMessages([
                'materias' => 'Alguna de las materias seleccionadas no es válida para este estudiante.',
            ]);
        }

        $yaInscripto = InscripcionMateria::query()
            ->where('id_usuario', $estudiante->id_usuario)
            ->where('id_ciclo', $ciclo->id_ciclo)
            ->whereIn('id_materia', $idsMaterias)
            ->exists();

        if ($yaInscripto) {
            throw ValidationException::withMessages([
                'materias' => "Ya estás matriculado en alguna de estas materias para el ciclo {$ciclo->anio}.",
            ]);
        }

        $notas = $this->notasVigentesPorMateria($estudiante);

        foreach ($materias as $materia) {
            $notaPropia = $notas->get($materia->id_materia);

            if ($this->situacionCoincide($notaPropia?->situacion, self::SITUACIONES_RESUELTAS_SIN_MATRICULAR)) {
                throw ValidationException::withMessages([
                    'materias' => "\"{$materia->nombre}\" ya está resuelta (aprobada o regularizada), no podés volver a matricularte.",
                ]);
            }

            if (!$this->cumpleCorrelativas($materia, $notas)) {
                throw ValidationException::withMessages([
                    'materias' => "No cumplís las correlativas de cursada para \"{$materia->nombre}\".",
                ]);
            }
        }

        // TODO/SUPUESTO: id_tipo_estudiante / id_estado_estudiante parecen ser
        // NOT NULL (el seeder siempre les carga un valor real, nunca null).
        // Uso el primero de cada catálogo como default, igual que el seeder,
        // pero esto es una adivinanza: confirmar cuál es el "tipo" y "estado"
        // correctos para un alumno que recién se matricula a una cursada
        // (¿"Regular"? ¿"Cursando"?) y reemplazar este ::first() por el id
        // correcto (o por una columna/constante de configuración).
        $tipoEstudiante = TipoEstudiante::first();
        $estadoEstudiante = EstadoEstudiante::first();

        // Transacción: si falla la inscripción de alguna materia (ej. un
        // NOT NULL que todavía no identificamos bien), no queremos que las
        // demás queden creadas a medias. O se guardan todas o ninguna.
        return DB::transaction(function () use ($materias, $estudiante, $ciclo, $tipoEstudiante, $estadoEstudiante) {
            $creadas = collect();

            foreach ($materias as $materia) {
                $creadas->push(InscripcionMateria::create([
                    'id_usuario'        => $estudiante->id_usuario,
                    'id_materia'        => $materia->id_materia,
                    'id_ciclo'          => $ciclo->id_ciclo,
                    'fecha_inscripcion' => now()->toDateString(),

                    'id_tipo_estudiante'   => $tipoEstudiante->id_tipo_estudiante ?? null,
                    'id_estado_estudiante' => $estadoEstudiante->id_estado_estudiante ?? null,
                    'contador_finales'     => 0,

                    // TODO/SUPUESTO: igual que arriba, el seeder siempre carga estos
                    // campos con datos reales (parecen de trámite de secretaría:
                    // libro/folio de un libro físico de actas, división de cursada).
                    // No tengo de dónde sacar el valor correcto en una matriculación
                    // por autogestión del alumno — confirmar con la secretaría si
                    // hace falta pedirlos en el form, autogenerarlos, o si en
                    // realidad estas columnas SÍ deberían ser nullable para este caso.
                    'fecha_vencimiento' => now()->addYears(2),
                    'libro'             => null,
                    'folio'             => null,
                    'division'          => null,
                ]));
            }

            return $creadas;
        });
    }

    // ────────────────────────────────────────────────────────────────
    // Cierre automático de matriculación → pasaje a "Libre"
    // ────────────────────────────────────────────────────────────────

    /**
     * Cierra la matriculación de UN ciclo ya vencido: cualquier materia que
     * el estudiante PODÍA haber matriculado (misma regla que "disponibles":
     * correlativas cumplidas, no aprobada/regularizada, no cursando en otro
     * lado) y no matriculó durante la ventana de ese ciclo, pasa a "Libre".
     *
     * Las materias "Regular" o "Aprobado/Promocionado" quedan afuera de
     * "disponibles" (ver SITUACIONES_RESUELTAS_SIN_MATRICULAR), así que
     * automáticamente quedan exceptuadas de este proceso — no hace falta
     * chequearlas de nuevo acá.
     *
     * IDEMPOTENTE: si ya existe una nota para ese alumno+materia+ciclo (por
     * ejemplo porque este comando ya corrió antes para este ciclo), no la
     * pisa. Así se puede llamar de nuevo sin duplicar ni corromper notas ya
     * cargadas por otro motivo.
     *
     * SUPUESTO: no tengo del modelo Estudiante cómo filtrar "solo estudiantes
     * activos" — si existe ese scope/estado, convendría aplicarlo acá para no
     * procesar de más.
     *
     * @return int cantidad de materias pasadas a "Libre"
     */
    public function cerrarMatriculacionCiclo(CicloLectivo $ciclo): int
    {
        $totalPasadasALibre = 0;

        Estudiante::with('carreras')->chunkById(100, function (Collection $estudiantes) use ($ciclo, &$totalPasadasALibre) {
            foreach ($estudiantes as $estudiante) {
                if ($estudiante->carreras->isEmpty()) {
                    continue;
                }

                $disponibles = $this->materiasDisponiblesParaCiclo($estudiante, $ciclo);

                foreach ($disponibles as $materia) {
                    $yaTieneNotaEnEsteCiclo = NotaCuatrimestre::query()
                        ->where('id_usuario', $estudiante->id_usuario)
                        ->where('id_materia', $materia->id_materia)
                        ->where('id_ciclo', $ciclo->id_ciclo)
                        ->exists();

                    if ($yaTieneNotaEnEsteCiclo) {
                        continue;
                    }

                    NotaCuatrimestre::create([
                        'id_usuario'  => $estudiante->id_usuario,
                        'id_materia'  => $materia->id_materia,
                        'id_ciclo'    => $ciclo->id_ciclo,
                        'situacion'   => 'Libre',
                        'fecha_carga' => now(),
                        // `valor` es NOT NULL en la base (confirmado por el
                        // error real: "Column 'valor' cannot be null"). Uso 0
                        // como placeholder de "sin nota numérica" para un
                        // pase a Libre automático, ya que no hubo evaluación.
                        'valor'       => 0,
                    ]);

                    $totalPasadasALibre++;
                }
            }
        }, 'id_usuario');

        return $totalPasadasALibre;
    }

    /**
     * Ciclo lectivo cuyo período de matriculación está abierto hoy.
     *
     * SUPUESTO: solo hay uno abierto a la vez. Si hubiera superposición de
     * fechas entre ciclos, se toma el de fecha_inicio más reciente.
     */
    protected function obtenerCicloConMatriculacionAbierta(): ?CicloLectivo
    {
        $hoy = now()->toDateString();

        return CicloLectivo::query()
            ->whereDate('inicio_matriculacion', '<=', $hoy)
            ->whereDate('fin_matriculacion', '>=', $hoy)
            ->orderByDesc('fecha_inicio')
            ->first();
    }

    /**
     * Notas del estudiante indexadas por id_materia (una sola por materia,
     * la más reciente por fecha_carga). Se usa para resolver correlativas,
     * a diferencia de `obtenerDatosCursada()` que indexa por materia+ciclo
     * porque ahí necesita distinguir cursadas repetidas en distintos ciclos.
     */
    protected function notasVigentesPorMateria(Estudiante $estudiante): Collection
    {
        return NotaCuatrimestre::query()
            ->where('id_usuario', $estudiante->id_usuario)
            ->orderByDesc('fecha_carga')
            ->get()
            ->unique('id_materia')
            ->keyBy('id_materia');
    }

    /**
     * Una materia cumple correlativas de cursada si, para cada correlativa
     * marcada con correlativa_cursada = true, la materia correlativa tiene
     * una nota cuya situación cae en SITUACIONES_POR_TABLA['aprobado'].
     *
     * Las correlativas marcadas solo con correlativa_final (para rendir,
     * no para cursar) no bloquean acá.
     */
    protected function cumpleCorrelativas(Materia $materia, Collection $notasPorMateria): bool
    {
        foreach ($materia->correlativas as $correlativa) {
            if (!$correlativa->correlativa_cursada) {
                continue;
            }

            $nota = $notasPorMateria->get($correlativa->id_materia_correlativa);

            if (!$nota || !$nota->situacion) {
                return false;
            }

            if (!$this->situacionCoincide($nota->situacion, self::SITUACIONES_POR_TABLA['aprobado'])) {
                return false;
            }
        }

        return true;
    }
}