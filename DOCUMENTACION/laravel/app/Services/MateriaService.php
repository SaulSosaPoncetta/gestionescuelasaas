<?php

namespace App\Services;

use App\Models\Carrera;
use App\Models\CicloLectivo;
use App\Models\Docente;
use App\Models\EstadoEstudiante;
use App\Models\Estudiante;
use App\Models\InscripcionMateria;
use App\Models\Materia;
use App\Models\MateriaDocente;
use App\Models\NotaCuatrimestre;
use App\Models\NotaFinal;
use App\Models\TipoEstudiante;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\ArchivoMateria;
use App\Models\TipoArchivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\EstudianteCarrera;

class MateriaService
{
    // -------------------------------------------------------------------------
    // CURSADA (perfil de carrera): materias agrupadas por año curricular
    // -------------------------------------------------------------------------

    public function materiasPorCarreraAgrupadas(Carrera $carrera, ?int $idCiclo = null): array
    {
        if (!$idCiclo) {
            $idCiclo = $this->cicloLectivoActivo()?->id_ciclo;
        }

        $materias = Materia::where('id_carrera', $carrera->id_carrera)
            ->where(function ($q) use ($idCiclo) {
                $q->where('id_ciclo', $idCiclo)->orWhereNull('id_ciclo');
            })
            ->with(['materiaDocentes' => function ($q) use ($idCiclo) {
                $q->where('id_ciclo', $idCiclo);
            }, 'materiaDocentes.docente.usuario'])
            ->orderBy('anio')
            ->orderBy('orden_anio')
            ->get();

        $agrupadas = [];

        foreach (range(1, 5) as $anio) {
            $agrupadas[Materia::nombreAnio($anio)] = $materias
                ->where('anio', $anio)
                ->values()
                ->map(function (Materia $materia) {
                    $docente = $materia->materiaDocentes->first()?->docente;

                    return (object) [
                        'id_materia' => $materia->id_materia,
                        'nombre'     => $materia->nombre,
                        'profesor'   => $docente ? (object) [
                            'usuario' => (object) ['apellido' => $docente->usuario->apellido],
                        ] : null,
                    ];
                });
        }

        return $agrupadas;
    }

    /**
     * Analítico de un alumno: todas las materias de su carrera agrupadas por
     * año, con el estado de cursada y de acreditación de cada una.
     *
     * "Acreditada" = promocionó la cursada (no necesita final) o tiene un
     * final aprobado (nota >= materia->nota_aprobada, default 4).
     * El texto de la celda de acreditación tiene que arrancar siempre con la
     * palabra "Acreditado" cuando corresponde: analiticoAlumno.js cuenta las
     * filas por ese texto para calcular el % de la carrera.
     */
    public function analiticoPorEstudiante(Estudiante $estudiante, ?int $idCarrera = null): array
    {
        // Si piden una carrera puntual, se usa esa (solo si el alumno la tiene);
        // si no, se mantiene el comportamiento de antes.
        $carrera = $idCarrera
            ? $this->carreraDelEstudiante($estudiante, $idCarrera)
            : $this->carreraParaAnalitico($estudiante);

        if (!$carrera) {
            return ['carrera' => null, 'materiasPorAnio' => []];
        }

        $materiasCarrera = Materia::where('id_carrera', $carrera->id_carrera)->get();

        // Un solo plan por analítico (si no, se mezclarían espacios de planes distintos).
        $idPlan = $this->planParaAnalitico($estudiante, $materiasCarrera);

        $materiasPlan = $materiasCarrera
            ->filter(fn (Materia $m) => $m->id_plan == $idPlan)
            ->values();

        $idsMaterias = $materiasPlan->pluck('id_materia');

        $cierres = NotaCuatrimestre::where('id_usuario', $estudiante->id_usuario)
            ->where('periodo', 'CIERRE')
            ->whereIn('id_materia', $idsMaterias)
            ->get();

        $finales = NotaFinal::where('id_usuario', $estudiante->id_usuario)
            ->whereIn('id_materia', $idsMaterias)
            ->get();

        // Un espacio curricular = todas las filas (una por ciclo) con el mismo nombre.
        $espacios = $materiasPlan
            ->groupBy('nombre')
            ->map(function ($grupo) {
                // Representante: la fila del ciclo más reciente.
                $rep = $grupo->sortByDesc('id_ciclo')->first();

                return (object) ['rep' => $rep, 'ids' => $grupo->pluck('id_materia')];
            })
            ->sortBy(fn ($e) => sprintf('%02d-%02d', (int) $e->rep->anio, (int) $e->rep->orden_anio))
            ->values();

        $agrupadas = [];

        foreach (range(1, 5) as $anio) {
            $agrupadas[Materia::nombreAnio($anio)] = $espacios
                ->filter(fn ($e) => (int) $e->rep->anio === $anio)
                ->values()
                ->map(function ($espacio) use ($cierres, $finales) {
                    $materia      = $espacio->rep;
                    $notaAprobada = $materia->nota_aprobada ?? 4;

                    $cierresEspacio = $cierres
                        ->whereIn('id_materia', $espacio->ids)
                        ->sortByDesc('fecha_carga');

                    // Preferimos el cierre que acredita; si no hay, el más reciente.
                    $cierre = $cierresEspacio->first(fn ($c) => $c->situacion === 'promocionado')
                        ?? $cierresEspacio->first(fn ($c) => in_array($c->situacion, ['aprobado', 'desaprobado', 'reprobado_directo'], true))
                        ?? $cierresEspacio->first();

                    $finalAprobado = $finales
                        ->whereIn('id_materia', $espacio->ids)
                        ->sortByDesc('fecha_carga')
                        ->first(fn ($f) => $f->valor >= $notaAprobada);

                    return (object) [
                        'id_materia'        => $cierre?->id_materia ?? $finalAprobado?->id_materia ?? $materia->id_materia,
                        'nombre'            => $materia->nombre,
                        'cursadaTexto'      => $this->textoCursada($cierre),
                        'acreditacionTexto' => $this->textoAcreditacion($materia, $cierre, $finalAprobado),
                    ];
                });
        }

        $totalMaterias = $espacios->count();
        $acreditadas = collect($agrupadas)
            ->flatten()
            ->filter(fn ($espacio) => str_starts_with($espacio->acreditacionTexto, 'Acreditado'))
            ->count();

        $this->registrarEgresoSiCorresponde($estudiante, $carrera, $acreditadas, $totalMaterias);

        return [
            'carrera'         => $carrera,
            'materiasPorAnio' => $agrupadas,
            'acreditadas'     => $acreditadas,
            'total'           => $totalMaterias,
            'porcentaje'      => $totalMaterias > 0 ? round(($acreditadas / $totalMaterias) * 100) : 0,
        ];
    }

    /**
     * Plan del analítico: el plan donde el alumno tiene más actividad
     * (inscripción, cursada o final). Si no tiene ninguna, el plan más bajo
     * de la carrera.
     */
    private function planParaAnalitico(Estudiante $estudiante, $materiasCarrera)
    {
        $idsActividad = InscripcionMateria::where('id_usuario', $estudiante->id_usuario)->pluck('id_materia')
            ->merge(NotaCuatrimestre::where('id_usuario', $estudiante->id_usuario)->pluck('id_materia'))
            ->merge(NotaFinal::where('id_usuario', $estudiante->id_usuario)->pluck('id_materia'))
            ->unique();

        $planConMasActividad = $materiasCarrera
            ->whereIn('id_materia', $idsActividad)
            ->groupBy('id_plan')
            ->sortByDesc(fn ($g) => $g->count())
            ->keys()
            ->first();

        return $planConMasActividad ?? $materiasCarrera->min('id_plan');
    }

    /**
     * Si el alumno acreditó el 100% de las materias de la carrera, lo marca
     * como Egresado en estudiante_carrera y registra la fecha de egreso (si
     * todavía no estaba marcado). De acá sale automáticamente el listado de
     * Egresados.
     */
    private function registrarEgresoSiCorresponde(Estudiante $estudiante, Carrera $carrera, int $acreditadas, int $total): void
    {
        if ($total === 0 || $acreditadas < $total) {
            return;
        }

        $matricula = \App\Models\EstudianteCarrera::where('id_usuario', $estudiante->id_usuario)
            ->where('id_carrera', $carrera->id_carrera)
            ->orderByDesc('cohorte')
            ->first();

        if (!$matricula || $matricula->fecha_egreso) {
            return; // sin matrícula cargada, o ya estaba marcado egresado
        }

        $estadoEgresado = \App\Models\EstadoCarrera::firstOrCreate(['estado' => 'Egresado']);

        DB::table('estudiante_carrera')
            ->where('id_usuario', $estudiante->id_usuario)
            ->where('id_carrera', $carrera->id_carrera)
            ->where('cohorte', $matricula->cohorte)
            ->update([
                'id_estado_carrera' => $estadoEgresado->id_estado_carrera,
                'fecha_egreso'      => now()->toDateString(),
                'updated_at'        => now(),
            ]);
    }
    /**
     * Devuelve la carrera pedida solo si el alumno está/estuvo en ella.
     */
    private function carreraDelEstudiante(Estudiante $estudiante, int $idCarrera): ?Carrera
    {
        return EstudianteCarrera::with('carrera')
            ->where('id_usuario', $estudiante->id_usuario)
            ->where('id_carrera', $idCarrera)
            ->first()
            ?->carrera;
    }
    /**
     * Determina la carrera a usar para el analítico de un alumno.
     *
     * Prioridad:
     *  1. Su matrícula explícita en estudiante_carrera (cohorte más reciente),
     *     si es que se cargó.
     *  2. Si no tiene matrícula cargada -hoy no hay ninguna pantalla que la
     *     genere-, se infiere la carrera a partir de las materias en las que
     *     el alumno tiene inscripción, cursada o final cargado: se toma la
     *     carrera a la que pertenecen la mayoría de esas materias.
     */
    private function carreraParaAnalitico(Estudiante $estudiante): ?Carrera
    {
        $carreraMatriculada = $estudiante->carreraActual();

        if ($carreraMatriculada) {
            return $carreraMatriculada;
        }

        $idsMaterias = InscripcionMateria::where('id_usuario', $estudiante->id_usuario)
            ->pluck('id_materia')
            ->merge(
                NotaCuatrimestre::where('id_usuario', $estudiante->id_usuario)->pluck('id_materia')
            )
            ->merge(
                NotaFinal::where('id_usuario', $estudiante->id_usuario)->pluck('id_materia')
            )
            ->unique();

        if ($idsMaterias->isEmpty()) {
            return null;
        }

        $idCarrera = Materia::whereIn('id_materia', $idsMaterias)
            ->selectRaw('id_carrera, COUNT(*) as total')
            ->groupBy('id_carrera')
            ->orderByDesc('total')
            ->value('id_carrera');

        return $idCarrera ? Carrera::find($idCarrera) : null;
    }

    private function textoCursada(?NotaCuatrimestre $cierre): string
    {
        if (!$cierre) {
            return 'Sin cursar';
        }

        $anio = $cierre->fecha_carga?->format('Y');

        return match ($cierre->situacion) {
            'promocionado', 'aprobado' => "Aprobada en {$anio} - Nota: " . number_format((float) $cierre->valor, 2),
            'desaprobado'              => "Regular en {$anio} - Nota: " . number_format((float) $cierre->valor, 2),
            'reprobado_directo'        => "Reprobada en {$anio}",
            default                    => 'Cursando',
        };
    }

    private function textoAcreditacion(Materia $materia, ?NotaCuatrimestre $cierre, $finalAprobado): string
    {
        if ($cierre && $cierre->situacion === 'promocionado') {
            return 'Acreditado el ' . $cierre->fecha_carga?->format('d/m/Y') . ' - Nota: ' . number_format((float) $cierre->valor, 2);
        }

        if ($finalAprobado) {
            return 'Acreditado el ' . $finalAprobado->fecha_carga?->format('d/m/Y') . ' - Nota: ' . number_format((float) $finalAprobado->valor, 2);
        }

        return 'Pendiente';
    }

    public function crear(Carrera $carrera, array $data): Materia
    {
        $tipoMateria = \App\Models\TipoMateria::firstOrCreate(['descripcion_tipo_materia' => 'Materia']);

        $archivo = $data['archivo'] ?? null;
        $archivoDescripcion = $data['archivo_descripcion'] ?? null;
        unset($data['archivo'], $data['archivo_descripcion']);

        $idCiclo = $data['id_ciclo'] ?? $this->cicloLectivoActivo()?->id_ciclo;
        unset($data['id_ciclo']);

        $materia = Materia::create(array_merge($data, [
            'id_carrera'         => $carrera->id_carrera,
            'id_ciclo'           => $idCiclo,
            'id_tipo_materia'    => $tipoMateria->id_tipo_materia,
            'nota_promocionable' => 7,
            'nota_aprobada'      => 4,
        ]));

        if ($archivo instanceof \Illuminate\Http\UploadedFile) {
            $path = $archivo->store('archivo_materia', 'public');

            $tipoArchivo = TipoArchivo::create([
                'descripcion' => $archivoDescripcion,
                'archivo'     => $path,
            ]);

            ArchivoMateria::create([
                'id_tipo_archivo' => $tipoArchivo->id_tipo_archivo,
                'id_materia'      => $materia->id_materia,
            ]);
        }

        return $materia;
    }

    public function getById(int $id): Materia
    {
        $materia = Materia::with([
            'carrera',
            'planEstudio',
            'materiaDocentes.docente.usuario',
            'materiaDocentes.docente.usuario.dnis',
        ])->findOrFail($id);

        $materia->archivo = ArchivoMateria::with('tipoArchivo')
            ->where('id_materia', $materia->id_materia)
            ->first()
            ?->tipoArchivo;

        return $materia;
    }

    public function actualizar(int $id, array $data): Materia
    {
        $materia = Materia::findOrFail($id);
        $materia->update($data);

        return $materia->fresh(['carrera', 'planEstudio']);
    }

    public function actualizarArchivo(Materia $materia, UploadedFile $archivo, ?string $descripcion): void
    {
        $archivoMateria = ArchivoMateria::where('id_materia', $materia->id_materia)->first();

        if ($archivoMateria?->id_tipo_archivo) {
            $tipoAnterior = TipoArchivo::find($archivoMateria->id_tipo_archivo);
            if ($tipoAnterior?->archivo) {
                Storage::disk('public')->delete($tipoAnterior->archivo);
            }
        }

        $path = $archivo->store('archivo_materia', 'public');

        if ($archivoMateria) {
            TipoArchivo::where('id_tipo_archivo', $archivoMateria->id_tipo_archivo)->update([
                'descripcion' => $descripcion,
                'archivo'     => $path,
            ]);
        } else {
            $tipoArchivo = TipoArchivo::create([
                'descripcion' => $descripcion,
                'archivo'     => $path,
            ]);

            ArchivoMateria::create([
                'id_tipo_archivo' => $tipoArchivo->id_tipo_archivo,
                'id_materia'      => $materia->id_materia,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // RESOLUCIÓN DE "LA MATERIA ACTUAL" PARA ALUMNO / DOCENTE
    //
    // Como cada ciclo lectivo genera una fila de Materia distinta con el
    // mismo `nombre` (ver MateriasPorCicloSeeder), un mismo alumno o
    // docente puede tener varias filas de id_materia distintas asociadas a
    // "la misma materia" a lo largo del tiempo (ej. un alumno que recursó,
    // un docente asignado en varios ciclos). Estos dos métodos resuelven,
    // a partir del nombre, cuál de esas filas es "la vigente" para mostrar
    // en el perfil de materia de cada uno.
    // -------------------------------------------------------------------------

    /**
     * Última materia (por ciclo lectivo, y ante empate por fecha de
     * inscripción) en la que el alumno se inscribió con ese nombre.
     */
    public function materiaActualParaEstudiante(Estudiante $estudiante, string $nombre): ?Materia
    {
        $idMateria = InscripcionMateria::query()
            ->join('materias', 'materias.id_materia', '=', 'inscripciones_materia.id_materia')
            ->join('ciclos_lectivos', 'ciclos_lectivos.id_ciclo', '=', 'inscripciones_materia.id_ciclo')
            ->where('inscripciones_materia.id_usuario', $estudiante->id_usuario)
            ->where('materias.nombre', $nombre)
            ->orderByDesc('ciclos_lectivos.anio')
            ->orderByDesc('inscripciones_materia.fecha_inscripcion')
            ->value('materias.id_materia');

        return $idMateria ? Materia::find($idMateria) : null;
    }

    /**
     * Última materia (por fecha_inicio de la asignación en materia_docente)
     * en la que el docente fue asignado con ese nombre. "Cuando entró" se
     * interpreta como el inicio de la asignación más reciente.
     */
    public function materiaActualParaDocente(Docente $docente, string $nombre): ?Materia
    {
        $idMateria = MateriaDocente::query()
            ->join('materias', 'materias.id_materia', '=', 'materia_docente.id_materia')
            ->where('materia_docente.id_usuario_docente', $docente->id_usuario)
            ->where('materias.nombre', $nombre)
            ->orderByDesc('materia_docente.fecha_inicio')
            ->value('materias.id_materia');

        return $idMateria ? Materia::find($idMateria) : null;
    }

    // -------------------------------------------------------------------------
    // PROFESORES ASIGNADOS
    // -------------------------------------------------------------------------

    public function docentesDisponibles(Materia $materia): Collection
    {
        $yaAsignados = $materia->materiaDocentes()->pluck('id_usuario_docente');

        return Docente::with('usuario')
            ->whereNotIn('id_usuario', $yaAsignados)
            ->get()
            ->sortBy(fn ($d) => $d->usuario->apellido);
    }

    public function asignarDocente(Materia $materia, array $data): MateriaDocente
    {
        return DB::transaction(function () use ($materia, $data) {
            return MateriaDocente::create([
                'id_materia'         => $materia->id_materia,
                'id_usuario_docente' => $data['id_docente'],
                'situacion_revista'  => $data['situacion_revista'] ?? null,
                'division'           => $data['division'] ?? null,
                'fecha_inicio'       => $data['fecha_inicio'] ?? null,
                'fecha_fin'          => $data['fecha_fin'] ?? null,
                'horas_semanales'    => $data['horas_semanales'] ?? null,
            ]);
        });
    }

    public function quitarDocente(Materia $materia, int $idDocente): void
    {
        MateriaDocente::where('id_materia', $materia->id_materia)
            ->where('id_usuario_docente', $idDocente)
            ->delete();
    }

    // -------------------------------------------------------------------------
    // CICLO LECTIVO ACTIVO
    // -------------------------------------------------------------------------

    public function cicloLectivoActivo(): ?CicloLectivo
    {
        return CicloLectivo::abiertos()
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id_ciclo')
            ->first()
            ?? CicloLectivo::orderByDesc('fecha_inicio')
                ->orderByDesc('id_ciclo')
                ->first();
    }

    // -------------------------------------------------------------------------
    // ALUMNOS DE LA MATERIA (agregar / cargar notas)
    // -------------------------------------------------------------------------

    public function alumnosDisponibles(Materia $materia, ?string $busqueda = null): Collection
    {
        $idCiclo = $materia->id_ciclo;

        $idsInscriptos = InscripcionMateria::where('id_materia', $materia->id_materia)
            ->when($idCiclo, fn ($q) => $q->where('id_ciclo', $idCiclo))
            ->pluck('id_usuario');

        $idsDeLaCarrera = DB::table('estudiante_carrera')
            ->where('id_carrera', $materia->id_carrera)
            ->pluck('id_usuario')
            ->unique();

        $query = Estudiante::with(['usuario', 'usuario.dnis'])
            ->whereIn('estudiantes.id_usuario', $idsDeLaCarrera)
            ->whereNotIn('estudiantes.id_usuario', $idsInscriptos);

        if (!empty($busqueda)) {
            $like = '%' . $busqueda . '%';
            $query->whereHas('usuario', function ($q) use ($like) {
                $q->where('nombre', 'like', $like)
                ->orWhere('apellido', 'like', $like);
            });
        }

        return $query->get()->sortBy(fn ($e) => $e->usuario->apellido)->values();
    }

    public function alumnosAsignados(Materia $materia): Collection
    {
        $idCiclo = $materia->id_ciclo;

        return InscripcionMateria::with(['estudiante.usuario', 'estudiante.usuario.dnis'])
            ->where('id_materia', $materia->id_materia)
            ->when($idCiclo, fn ($q) => $q->where('id_ciclo', $idCiclo))
            ->get();
    }

    public function sincronizarAlumnos(Materia $materia, array $idsUsuario): void
    {
        $idCiclo = $materia->id_ciclo;

        if (!$idCiclo) {
            throw new \RuntimeException('Esta materia no tiene un ciclo lectivo asignado. No se pueden agregar alumnos.');
        }

        DB::transaction(function () use ($materia, $idsUsuario, $idCiclo) {

            // Baja de los que ya no están en la lista enviada
            InscripcionMateria::where('id_materia', $materia->id_materia)
                ->where('id_ciclo', $idCiclo)
                ->whereNotIn('id_usuario', $idsUsuario)
                ->delete();

            $tipoRegular  = TipoEstudiante::firstOrCreate(['descripcion_tipo' => 'Regular']);
            $estadoActivo = EstadoEstudiante::firstOrCreate(['descripcion_estado' => 'Activo']);

            foreach ($idsUsuario as $idUsuario) {
                InscripcionMateria::firstOrCreate([
                    'id_usuario' => $idUsuario,
                    'id_materia' => $materia->id_materia,
                    'id_ciclo'   => $idCiclo,
                ], [
                    'fecha_inscripcion'    => now(),
                    'id_tipo_estudiante'   => $tipoRegular->id_tipo_estudiante,
                    'id_estado_estudiante' => $estadoActivo->id_estado_estudiante,
                ]);
            }
        });
    }

    // -------------------------------------------------------------------------
    // CARGA DE NOTAS
    // -------------------------------------------------------------------------

    /**
     * Devuelve, por cada alumno inscripto, sus notas de 1°C / 2°C / Rec.-Prefinal
     * y si tiene un cierre de cursada cargado (reprobado directo o cierre normal).
     */
    public function datosCargarNotas(Materia $materia): array
    {
        $idCiclo = $materia->id_ciclo;

        $inscripciones = InscripcionMateria::with('estudiante.usuario')
            ->where('id_materia', $materia->id_materia)
            ->when($idCiclo, fn ($q) => $q->where('id_ciclo', $idCiclo))
            ->get();

        $notas = NotaCuatrimestre::where('id_materia', $materia->id_materia)
            ->when($idCiclo, fn ($q) => $q->where('id_ciclo', $idCiclo))
            ->get()
            ->groupBy('id_usuario');

        $cursando = [];
        $reprobados = [];

        foreach ($inscripciones as $inscripcion) {
            $notasAlumno = $notas->get($inscripcion->id_usuario, collect())
                ->keyBy('periodo');

            $fila = (object) [
                'id_usuario' => $inscripcion->id_usuario,
                'nombre'     => $inscripcion->estudiante->usuario->apellido . ', ' . $inscripcion->estudiante->usuario->nombre,
                'primerC'    => $notasAlumno->get('1C'),
                'segundoC'   => $notasAlumno->get('2C'),
                'recPrefinal'=> $notasAlumno->get('REC'),
                'cierre'     => $notasAlumno->get('CIERRE'),
            ];

            if ($fila->cierre && $fila->cierre->situacion === 'reprobado_directo') {
                $reprobados[] = $fila;
            } else {
                $cursando[] = $fila;
            }
        }

        return compact('cursando', 'reprobados');
    }

    /**
     * Guarda las notas cargadas para cada alumno. Si $cierreAutomatico está
     * activo, calcula el cierre de cursada en base a la situación de ambos
     * cuatrimestres.
     */
    public function guardarNotas(Materia $materia, array $filas): void
    {
        $idCiclo = $materia->id_ciclo;

        DB::transaction(function () use ($materia, $filas, $idCiclo) {
            foreach ($filas as $fila) {
                $idUsuario = $fila['id_usuario'];

                foreach (['1C', '2C', 'REC', 'CIERRE'] as $periodo) {
                    if (!isset($fila[$periodo])) {
                        continue;
                    }

                    if (empty($fila[$periodo]['valor']) && empty($fila[$periodo]['situacion'])) {
                        continue;
                    }

                    $this->guardarNotaPeriodo($materia, $idUsuario, $idCiclo, $periodo, $fila[$periodo]);
                }
            }
        });
    }

    private function guardarNotaPeriodo(Materia $materia, int $idUsuario, ?int $idCiclo, string $periodo, array $datos): void
    {
        NotaCuatrimestre::updateOrCreate([
            'id_materia' => $materia->id_materia,
            'id_usuario' => $idUsuario,
            'id_ciclo'   => $idCiclo,
            'periodo'    => $periodo,
        ], [
            'valor'      => $datos['valor'] ?? 0,
            'situacion'  => $datos['situacion'] ?? null,
            'fecha_carga'=> now(),
        ]);
    }

    /**
     * Calcula el cierre de cursada (situación + nota promedio) a partir de
     * las notas cargadas de 1°C, 2°C y, si hizo falta, el recuperatorio
     * (Rec./Prefinal). Devuelve ['situacion' => ?string, 'valor' => ?float].
     *
     * $materiaPromocionDirecta indica si ESTA materia es de las que se
     * acreditan siempre por promoción directa (sin final). Es la única
     * condición bajo la cual la situación "promocion_directa" puede cerrar
     * como tal; en cualquier otra materia esa situación no debería llegar
     * (la validación del controller ya la filtra), pero igual se chequea acá
     * como segunda barrera.
     *
     * Reglas de SITUACIÓN (nunca se deciden por el número, solo por estas
     * categorías — un promedio de 7+ no implica promoción si no se cumplió
     * la condición real de promoción en cada cuatrimestre):
     *
     *  - Un solo cuatrimestre cargado (la materia es de un solo cuatrimestre,
     *    o el otro todavía no se cargó): el cierre es esa misma situación.
     *    Si esa única situación fue Desaprobado y hay recuperatorio cargado,
     *    el recuperatorio decide (Aprobado o Desaprobado).
     *  - Desaprobado + Desaprobado                  => Desaprobado
     *  - Aprobado + Desaprobado (cualquier orden)    => pendiente de examen
     *    flotante hasta que se cargue el recuperatorio; ahí:
     *      · Rec. aprobado    => Aprobado
     *      · Rec. desaprobado => Desaprobado
     *  - Aprobado + Aprobado                         => Aprobado
     *  - Promocionado + Aprobado (cualquier orden)   => Aprobado
     *  - Promocionado + Promocionado                 => Promocionado
     *  - Promoción directa + Promoción directa       => Promoción directa
     *    (solo aplica a materias marcadas como de promoción directa)
     *  - Promoción directa + cualquier otra cosa     => Desaprobado
     *    (la promoción directa es "sí o sí": si un cuatrimestre no llega,
     *    no hay término medio)
     *
     * Reglas de PROMEDIO (numérico, independiente de la situación):
     *  - Promedia los cuatrimestres que tengan nota cargada.
     *  - Si hubo recuperatorio (porque un cuatrimestre se perdió), el
     *    recuperatorio REEMPLAZA la nota del cuatrimestre perdido en el
     *    promedio (no se promedian los 3 juntos) — el promedio queda entre
     *    el cuatrimestre aprobado y el recuperatorio.
     */
    private function calcularCierre(array $fila, bool $materiaPromocionDirecta): array
    {
        $s1 = $fila['1C']['situacion'] ?? null;
        $s2 = $fila['2C']['situacion'] ?? null;
        $sRec = $fila['REC']['situacion'] ?? null;

        $v1 = $this->valorNumerico($fila['1C']['valor'] ?? null);
        $v2 = $this->valorNumerico($fila['2C']['valor'] ?? null);
        $vRec = $this->valorNumerico($fila['REC']['valor'] ?? null);

        // Ninguno de los dos cuatrimestres tiene situación cargada: nada que cerrar.
        if (!$s1 && !$s2) {
            return ['situacion' => null, 'valor' => null];
        }

        // Un solo cuatrimestre cargado (materia de un cuatrimestre, o falta el otro)
        if (!$s1 || !$s2) {
            $situacionUnica = $s1 ?? $s2;

            if ($situacionUnica === 'promocion_directa' && !$materiaPromocionDirecta) {
                // Dato inconsistente con el tipo de materia: no cerramos
                // automáticamente, que lo revise la secretaría a mano.
                return ['situacion' => null, 'valor' => null];
            }

            $valorUnico = $s1 ? $v1 : $v2;

            if ($situacionUnica === 'desaprobado' && $sRec) {
                return [
                    'situacion' => $sRec === 'aprobado' ? 'aprobado' : 'desaprobado',
                    'valor'     => $this->promediar([$valorUnico, $vRec]),
                ];
            }

            return ['situacion' => $situacionUnica, 'valor' => $valorUnico];
        }

        $situaciones = [$s1, $s2];

        // Ambos cuatrimestres con la misma situación
        if ($s1 === $s2) {
            if ($s1 === 'promocion_directa' && !$materiaPromocionDirecta) {
                // Igual que arriba: esta materia no es de promoción directa,
                // no cerramos automáticamente con ese valor.
                return ['situacion' => null, 'valor' => $this->promediar([$v1, $v2])];
            }

            $situacionFinal = match ($s1) {
                'desaprobado'        => 'desaprobado',
                'aprobado'           => 'aprobado',
                'promocionado'       => 'promocionado',
                'promocion_directa'  => 'promocion_directa',
                default              => null,
            };

            return ['situacion' => $situacionFinal, 'valor' => $this->promediar([$v1, $v2])];
        }

        // Promoción directa mezclada con cualquier otra situación => Desaprobado (es "sí o sí")
        if (in_array('promocion_directa', $situaciones, true)) {
            return ['situacion' => 'desaprobado', 'valor' => $this->promediar([$v1, $v2])];
        }

        // Promocionado + Aprobado => Aprobado
        if (in_array('promocionado', $situaciones, true) && in_array('aprobado', $situaciones, true)) {
            return ['situacion' => 'aprobado', 'valor' => $this->promediar([$v1, $v2])];
        }

        // Aprobado + Desaprobado, o Promocionado + Desaprobado => examen flotante
        // hasta que se cargue el Rec. El Rec. reemplaza (no se suma a) la nota
        // del cuatrimestre perdido para el promedio.
        if (in_array('desaprobado', $situaciones, true)) {
            $valorDelQueAproboo = $s1 === 'desaprobado' ? $v2 : $v1;

            if ($sRec === 'aprobado') {
                return ['situacion' => 'aprobado', 'valor' => $this->promediar([$valorDelQueAproboo, $vRec])];
            }

            if ($sRec === 'desaprobado') {
                return ['situacion' => 'desaprobado', 'valor' => $this->promediar([$valorDelQueAproboo, $vRec])];
            }

            return ['situacion' => 'examen_flotante', 'valor' => null];
        }

        return ['situacion' => null, 'valor' => null];
    }

    private function valorNumerico($valor): ?float
    {
        return ($valor !== null && $valor !== '') ? (float) $valor : null;
    }

    private function promediar(array $valores): ?float
    {
        $valores = array_filter($valores, fn ($v) => $v !== null);

        if (empty($valores)) {
            return null;
        }

        return round(array_sum($valores) / count($valores), 2);
    }

    public function reprobarDirecto(Materia $materia, int $idUsuario): void
    {
        $this->guardarNotaPeriodo($materia, $idUsuario, $materia->id_ciclo, 'CIERRE', [
            'valor'     => null,
            'situacion' => 'reprobado_directo',
        ]);
    }

    public function restaurarCursando(Materia $materia, int $idUsuario): void
    {
        NotaCuatrimestre::where('id_materia', $materia->id_materia)
            ->where('id_usuario', $idUsuario)
            ->when($materia->id_ciclo, fn ($q) => $q->where('id_ciclo', $materia->id_ciclo))
            ->where('periodo', 'CIERRE')
            ->delete();
    }
    // -------------------------------------------------------------------------
    // ESTADÍSTICAS (perfil de materia)
    // -------------------------------------------------------------------------

    /**
     * Estadísticas para los anillos del perfil de materia:
     *  - Distribución por sexo de los alumnos inscriptos (ciclo activo).
     *  - % de aprobados en cursada (cierre = aprobado/promocionado/promocion_directa)
     *    sobre el total de inscriptos con cierre cargado.
     *  - % de aprobados en final (última nota de cada alumno >= nota_aprobada)
     *    sobre el total de alumnos con al menos un final rendido.
     */
    public function estadisticasMateria(Materia $materia): array
    {
        $idCiclo = $materia->id_ciclo;

        $idsInscriptos = InscripcionMateria::where('id_materia', $materia->id_materia)
            ->when($idCiclo, fn ($q) => $q->where('id_ciclo', $idCiclo))
            ->pluck('id_usuario');

        // ─── Sexo ────────────────────────────────────────────────────────────
        $sexoStats = ['mujer' => 0, 'hombre' => 0, 'otro' => 0];
        $mapaSexo = ['F' => 'mujer', 'M' => 'hombre', 'X' => 'otro'];

        \App\Models\Usuario::whereIn('id_usuario', $idsInscriptos)
            ->selectRaw('genero, COUNT(*) as total')
            ->groupBy('genero')
            ->get()
            ->each(function ($fila) use (&$sexoStats, $mapaSexo) {
                $clave = $mapaSexo[$fila->genero] ?? 'otro';
                $sexoStats[$clave] += $fila->total;
            });

        // ─── Aprobados en cursada ────────────────────────────────────────────
        $cierres = NotaCuatrimestre::where('id_materia', $materia->id_materia)
            ->where('periodo', 'CIERRE')
            ->whereIn('id_usuario', $idsInscriptos)
            ->when($idCiclo, fn ($q) => $q->where('id_ciclo', $idCiclo))
            ->get();

        $cursadaStats = ['promocionado' => 0, 'aprobado' => 0, 'desaprobado' => 0];

        foreach ($cierres as $cierre) {
            if (in_array($cierre->situacion, ['promocionado', 'promocion_directa'], true)) {
                $cursadaStats['promocionado']++;
            } elseif ($cierre->situacion === 'aprobado') {
                $cursadaStats['aprobado']++;
            } elseif (in_array($cierre->situacion, ['desaprobado', 'reprobado_directo'], true)) {
                $cursadaStats['desaprobado']++;
            }
        }

        // ─── Aprobados en final ──────────────────────────────────────────────
        $notaAprobada = $materia->nota_aprobada ?? 4;

        $ultimosFinales = NotaFinal::where('id_materia', $materia->id_materia)
            ->whereNotNull('valor')
            ->when($idCiclo, function ($q) use ($idCiclo) {
                $q->whereHas('mesaExamen', function ($mq) use ($idCiclo) {
                    $mq->where('id_ciclo', $idCiclo);
                });
            })
            ->orderByDesc('fecha_carga')
            ->get()
            ->groupBy('id_usuario')
            ->map(fn ($intentos) => $intentos->first());

        $totalFinales = $ultimosFinales->count();
        $aprobadosFinal = $ultimosFinales->filter(fn ($f) => $f->valor >= $notaAprobada)->count();
        $porcentajeAprobadosFinal = $totalFinales > 0 ? round($aprobadosFinal / $totalFinales * 100) : 0;

        return compact('sexoStats', 'cursadaStats', 'porcentajeAprobadosFinal');
    }
}