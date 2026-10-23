<?php

namespace App\Services;

use App\Models\Llamado;
use App\Models\MesaExamen;
use App\Models\NotaFinal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Models\CicloLectivo;
use App\Models\ArchivoExamen;
use App\Models\TipoArchivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MesaExamenService
{
    /**
     * Requiere en el Model MesaExamen:
     *
     *   public function docentes()
     *   {
     *       return $this->belongsToMany(Docente::class, 'mesa_examen_docente', 'id_mesa', 'id_docente')
     *           ->withPivot('cargo'); // 'titular' | 'vocal' (no confundir con usuarios.id_rol)
     *   }
     *
     *   public function llamado()
     *   {
     *       return $this->belongsTo(Llamado::class, 'id_llamado', 'id_llamado');
     *   }
     *
     *   public function notasFinal()
     *   {
     *       return $this->hasMany(NotaFinal::class, 'id_mesa', 'id_mesa');
     *   }
     *
     * "estudiantes" ya NO es una relación belongsToMany: se arma en find()
     * a partir de notasFinal, porque notas_final tiene columnas propias
     * (id_materia, id_tipo_archivo) que un attach/sync de pivot no podría
     * completar correctamente.
     */
    protected array $with = [
        'tipoMesa',
        'llamado',
        'materia',
        'materia.carrera',
        'administrador',
        'cicloLectivo',
        'docentes',
        'docentes.usuario',
    ];

    

    /**
     * Pantalla "Fechas de finales": listado con estado calculado (SIN ALUMNOS /
     * CERRADO / ABIERTA) en base a notasFinal.
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters)
            ->withCount('notasFinal as estudiantes_count')
            ->withCount([
                'notasFinal as estudiantes_sin_nota_count' => function ($q) {
                    $q->whereNull('valor');
                },
            ]);

        $mesas = $query->orderBy('fecha')->paginate($perPage);

        $mesas->getCollection()->transform(fn (MesaExamen $mesa) => $this->appendEstado($mesa));

        return $mesas;
    }
     /**
     * Ciclo lectivo "actual": el que está marcado como 'abierto'; si hay
     * más de uno (no debería, pero por las dudas) se toma el de año más
     * alto. Si ninguno está abierto, cae al de año más alto como fallback.
     */
    public function cicloActual(): ?CicloLectivo
    {
        return CicloLectivo::where('estado', 'abierto')
            ->orderByDesc('anio')
            ->first()
            ?? CicloLectivo::orderByDesc('anio')->first();
    }

    /**
     * Pantalla "Historial de finales". Carrera y Llamado obligatorios (los
     * valida el controller), Año opcional. No calcula 'estado'.
     */
    public function getHistorial(array $filters, int $perPage = 100): LengthAwarePaginator
    {
        return $this->baseQuery($filters)->orderBy('fecha')->paginate($perPage);
    }

    protected function baseQuery(array $filters)
{
    $query = MesaExamen::query()->with($this->with);

    if (!empty($filters['id_ciclo'])) {
        $query->where('id_ciclo', $filters['id_ciclo']);
    }

    if (!empty($filters['id_tipo_mesa'])) {
        $query->where('id_tipo_mesa', $filters['id_tipo_mesa']);
    }

    if (!empty($filters['id_llamado'])) {
        $query->where('id_llamado', $filters['id_llamado']);
    }

    if (!empty($filters['id_materia'])) {
        $query->where('id_materia', $filters['id_materia']);
    }

    if (!empty($filters['id_carrera'])) {
        $query->whereHas('materia', function ($q) use ($filters) {
            $q->where('id_carrera', $filters['id_carrera']);
        });
    }

    if (!empty($filters['anio'])) {
        $query->whereHas('materia', function ($q) use ($filters) {
            $q->where('anio', $filters['anio']);
        });
    }

    // Nuevo: usado por finalesDocView() para que un docente solo vea
    // las mesas donde participa como titular o vocal (cargo en el
    // pivot mesa_examen_docente).
    if (!empty($filters['id_docente'])) {
        $query->whereHas('docentes', function ($q) use ($filters) {
            $q->where('docentes.id_usuario', $filters['id_docente']);
        });
    }

    if (!empty($filters['fecha_desde'])) {
        $query->whereDate('fecha', '>=', $filters['fecha_desde']);
    }

    if (!empty($filters['fecha_hasta'])) {
        $query->whereDate('fecha', '<=', $filters['fecha_hasta']);
    }

    if (!empty($filters['aula_desde'])) {
        $query->where('aula', '>=', $filters['aula_desde']);
    }

    if (!empty($filters['aula_hasta'])) {
        $query->where('aula', '<=', $filters['aula_hasta']);
    }

    if (!empty($filters['estudiantes']) && $filters['estudiantes'] !== 'todas') {
        if ($filters['estudiantes'] === 'con') {
            $query->has('notasFinal');
        } elseif ($filters['estudiantes'] === 'sin') {
            $query->doesntHave('notasFinal');
        }
    }
        // Fechas de finales: solo mesas que no cerraron todavía (ver appendEstado:
    // una mesa cierra al terminar el día siguiente a la fecha del examen).
    if (!empty($filters['solo_vigentes'])) {
        $query->where('fecha', '>', now()->subDay()->endOfDay());
    }

    // Informes/Historial: solo mesas ya cerradas (mínimo 2 días desde la fecha).
    if (!empty($filters['solo_vencidos'])) {
        $query->where('fecha', '<=', now()->subDay()->endOfDay());
    }

    return $query;
}
    /**
     * Una mesa está ABIERTA el día del examen y todo el día siguiente
     * (cierra a partir de las 00:00 del segundo día posterior a la fecha).
     * No depende de si tiene alumnos cargados ni de si faltan notas: una
     * mesa recién creada (sin alumnos todavía) ya aparece como ABIERTA.
     */
    protected function appendEstado(MesaExamen $mesa): MesaExamen
    {
        $fechaLimite = optional($mesa->fecha)?->copy()->addDay()->endOfDay();
        $vencida = $fechaLimite && now()->greaterThan($fechaLimite);

        $mesa->estado = $vencida ? 'CERRADO' : 'ABIERTA';

        return $mesa;
    }

    /**
     * Pantalla "Mesa de final": detalle con docentes (presidente/vocal según
     * columna 'cargo'), y estudiantes armados a partir de notasFinal
     * (nota = 'valor', cant = conteo de intentos previos del mismo
     * estudiante para la misma materia).
     */
    public function find(int $id): ?MesaExamen
    {
        $mesa = MesaExamen::with(array_merge($this->with, [
            'notasFinal.estudiante.usuario',
        ]))->find($id);
        

        if (!$mesa) {
            return null;
        }

        $mesa->archivo = ArchivoExamen::with('tipoArchivo')
        ->where('id_mesa', $mesa->id_mesa)
        ->first()
        ?->tipoArchivo;

        $mesa->presidente = $mesa->docentes->firstWhere('pivot.cargo', 'titular');
        $mesa->vocales = $mesa->docentes->where('pivot.cargo', 'vocal')->values();

        $mesa->estudiantes = $mesa->notasFinal
            // Si hay filas duplicadas para el mismo alumno en esta mesa,
            // nos quedamos con la más reciente (mayor id_nota).
            ->sortByDesc('id_nota')
            ->unique('id_usuario')
            ->map(function (NotaFinal $notaFinal) {
                $estudiante = $notaFinal->estudiante;

                $estudiante->id_nota = $notaFinal->id_nota;
                $estudiante->nota = $notaFinal->valor;
                $estudiante->cant = NotaFinal::where('id_materia', $notaFinal->id_materia)
                    ->where('id_usuario', $notaFinal->id_usuario)
                    ->count();

                return $estudiante;
            })
            ->values();

        return $mesa;
    }

    public function findOrFail(int $id): MesaExamen
    {
        $mesa = $this->find($id);

        abort_if(!$mesa, 404, 'Mesa de examen no encontrada');

        return $mesa;
    }

    /**
     * Valida que la fecha de una mesa sea coherente con el llamado elegido
     * (el mes de la fecha tiene que caer dentro de los meses de ese
     * llamado) y que no esté a más de 1 año de hoy. El caso de cambio de
     * año calendario (ej. crear en diciembre una mesa de febrero del año
     * que viene) se permite: se compara contra la fecha real
     * (now()->addYear()), no contra el número de año.
     *
     * @throws \InvalidArgumentException
     */
    protected function validarFecha(?int $idLlamado, $fecha): void
    {
        $fecha = Carbon::parse($fecha);

        if ($fecha->gt(now()->addYear())) {
            throw new \InvalidArgumentException(
                'La fecha no puede ser más de un año posterior a hoy.'
            );
        }

        if (!$idLlamado) {
            return;
        }

        $llamado = Llamado::find($idLlamado);

        if (!$llamado) {
            return; // ya lo habrá rechazado la validación 'exists:llamados,...' antes de llegar acá
        }

        $mesesValidos = $llamado->meses_validos;

        if ($mesesValidos && !in_array($fecha->month, $mesesValidos, true)) {
            throw new \InvalidArgumentException(
                "La fecha no corresponde al llamado '{$llamado->descripcion}': tiene que ser en el mes "
                . implode(' o ', $mesesValidos) . '.'
            );
        }
    }

    /**
     * Pantalla "Agregar Final". Crea 1 o 2 mesas (una por fecha cargada).
     * Los docentes se sincronizan con su cargo ('titular' / 'vocal').
     *
     * 'habilitado' no se pide en el form de "Agregar Final": toda mesa
     * nueva se crea habilitada (ABIERTA) por default, se pise o no algo
     * distinto en $data.
     */
    public function create(array $data): Collection
    {
        $fechas = $data['fechas'];
        unset($data['fechas']);

        foreach ($fechas as $fecha) {
            $this->validarFecha($data['id_llamado'] ?? null, $fecha);
        }

        $docenteTitular = $data['docente_titular'] ?? null;
        $docenteVocal = $data['docente_vocal'] ?? null;
        unset($data['docente_titular'], $data['docente_vocal']);

        $archivo = $data['archivo'] ?? null;
        $archivoDescripcion = $data['archivo_descripcion'] ?? null;
        unset($data['archivo'], $data['archivo_descripcion']);

        $data['habilitado'] = true;

        return DB::transaction(function () use ($data, $fechas, $docenteTitular, $docenteVocal, $archivo, $archivoDescripcion) {
            $idTipoArchivo = null;

            if ($archivo instanceof UploadedFile) {
                $path = $archivo->store('archivo_examen', 'public');

                $idTipoArchivo = TipoArchivo::create([
                    'descripcion' => $archivoDescripcion,
                    'archivo'     => $path,
                ])->id_tipo_archivo;
            }

            $mesasCreadas = collect();

            foreach ($fechas as $fecha) {
                $mesa = MesaExamen::create(array_merge($data, ['fecha' => $fecha]));

                $docentesSync = [];
                if ($docenteTitular) {
                    $docentesSync[$docenteTitular] = ['cargo' => 'titular'];
                }
                if ($docenteVocal) {
                    $docentesSync[$docenteVocal] = ['cargo' => 'vocal'];
                }
                if (!empty($docentesSync)) {
                    $mesa->docentes()->sync($docentesSync);
                }

                if ($idTipoArchivo) {
                    ArchivoExamen::create([
                        'id_tipo_archivo' => $idTipoArchivo,
                        'id_usuario'      => $data['id_usuario_administracion'],
                        'id_mesa'         => $mesa->id_mesa,
                    ]);
                }

                $mesasCreadas->push($mesa->load($this->with));
            }

            return $mesasCreadas;
        });
    }
    public function actualizarArchivo(MesaExamen $mesa, UploadedFile $archivo, ?string $descripcion, int $idUsuario): void
    {
        $archivoExamen = ArchivoExamen::where('id_mesa', $mesa->id_mesa)->first();

        if ($archivoExamen?->id_tipo_archivo) {
            $tipoAnterior = TipoArchivo::find($archivoExamen->id_tipo_archivo);
            if ($tipoAnterior?->archivo) {
                Storage::disk('public')->delete($tipoAnterior->archivo);
            }
        }

        $path = $archivo->store('archivo_examen', 'public');

        if ($archivoExamen) {
            TipoArchivo::where('id_tipo_archivo', $archivoExamen->id_tipo_archivo)->update([
                'descripcion' => $descripcion,
                'archivo'     => $path,
            ]);
        } else {
            $tipoArchivo = TipoArchivo::create([
                'descripcion' => $descripcion,
                'archivo'     => $path,
            ]);

            ArchivoExamen::create([
                'id_tipo_archivo' => $tipoArchivo->id_tipo_archivo,
                'id_usuario'      => $idUsuario,
                'id_mesa'         => $mesa->id_mesa,
            ]);
        }
    }
    
    /**
 * PUT/PATCH /finales/{id}
 * Edición administrativa de la mesa (datos de la mesa: materia, fecha,
 * hora, presidente, vocal, aula). Distinto del botón "Modificar" de
 * "Mesa de final" para las notas (ver updateNotas más abajo).
 */
public function update(MesaExamen $mesa, array $data): MesaExamen
{
    if (array_key_exists('fecha', $data) || array_key_exists('id_llamado', $data)) {
        $this->validarFecha(
            $data['id_llamado'] ?? $mesa->id_llamado,
            $data['fecha'] ?? $mesa->fecha
        );
    }

    $docenteTitular = $data['docente_titular'] ?? null;
    $docenteVocal = $data['docente_vocal'] ?? null;
    unset($data['docente_titular'], $data['docente_vocal']);

    $mesaLimpia = $mesa->fresh();
    $mesaLimpia->update($data);

    if ($docenteTitular || $docenteVocal) {
        $docentesSync = [];
        if ($docenteTitular) {
            $docentesSync[$docenteTitular] = ['cargo' => 'titular'];
        }
        if ($docenteVocal) {
            $docentesSync[$docenteVocal] = ['cargo' => 'vocal'];
        }
        $mesaLimpia->docentes()->sync($docentesSync);
    }

    return $this->find($mesaLimpia->id_mesa);
}

    /**
     * Pantalla "Agregar Alumno a final". Crea la fila en notas_final sin
     * 'valor' todavía (para que el estudiante quede listado en la mesa).
     * 'fecha_carga' es NOT NULL en la base (sin default), así que se setea
     * igual aunque todavía no haya nota; se vuelve a pisar cuando se cargue
     * la nota real en cargarNotas().
     */
    public function addEstudiantes(MesaExamen $mesa, array $idsEstudiantes): MesaExamen
    {
        foreach ($idsEstudiantes as $idUsuario) {
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
        }

        return $this->find($mesa->id_mesa);
    }


    /**
     * Guarda notas. $notasPorEstudiante: [ id_usuario => valor, ... ].
     * Si el estudiante ya tenía fila en notas_final para esta mesa, la
     * actualiza; si no, la crea (por si "Cargar notas" se usa sin haber
     * pasado antes por "Agregar Alumno a final").
     */
    public function cargarNotas(MesaExamen $mesa, array $notasPorEstudiante): MesaExamen
    {
        foreach ($notasPorEstudiante as $idUsuario => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }

            NotaFinal::updateOrCreate(
                [
                    'id_mesa'    => $mesa->id_mesa,
                    'id_usuario' => $idUsuario,
                ],
                [
                    'id_materia'  => $mesa->id_materia,
                    'valor'       => $valor,
                    'fecha_carga' => now(),
                ]
            );
        }

        return $this->find($mesa->id_mesa);
    }
}