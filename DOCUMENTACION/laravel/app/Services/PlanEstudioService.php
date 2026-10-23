<?php

namespace App\Services;

use App\Models\PlanEstudio;
use App\Models\ResolucionCarrera;
use App\Models\PlanResolucion;
use App\Models\TipoArchivo;
use App\Models\Carrera;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Estudiante;

class PlanEstudioService
{
    public function buscarPlanResoluciones(?string $filtro = null): Collection
    {
        return PlanResolucion::with(['plan', 'resolucion', 'carrera'])
            ->when($filtro, function ($q) use ($filtro) {
                $q->whereHas('plan', fn($qq) => $qq->where('titulo', 'like', "%$filtro%"))
                  ->orWhereHas('resolucion', fn($qq) => $qq->where('descripcion', 'like', "%$filtro%"))
                  ->orWhereHas('carrera', fn($qq) => $qq->where('nombre', 'like', "%$filtro%"));
            })
            ->get();
    }

    public function buscarPorId(int $id): PlanResolucion
    {
        return PlanResolucion::with(['plan', 'resolucion', 'carrera', 'tipoArchivo'])
            ->findOrFail($id);
    }

    public function crearPlanCompleto(array $data, UploadedFile $archivo): PlanResolucion
    {
        return DB::transaction(function () use ($data, $archivo) {
            $plan = PlanEstudio::create([
                'titulo'      => $data['titulo'],
                'descripcion' => $data['plan_descripcion'] ?? '',
            ]);

            $resolucion = ResolucionCarrera::create([
                'descripcion' => $data['resolucion_descripcion'],
            ]);

            $path = $archivo->store('tipos_archivo', 'public');

            $tipoArchivo = TipoArchivo::create([
                'descripcion' => $data['archivo_descripcion'],
                'archivo'     => $path,
            ]);

            $planResolucion = PlanResolucion::create([
                'id_plan'         => $plan->id_plan,
                'id_resolucion'   => $resolucion->id_resolucion,
                'id_carrera'      => $data['id_carrera'] ?? null,
                'id_tipo_archivo' => $tipoArchivo->id_tipo_archivo,
                'total_horas'     => $data['total_horas'],
                'fecha'           => $data['fecha'],
            ]);

            return $this->buscarPorId($planResolucion->id);
        });
    }

    public function actualizarPlanCompleto(
        int $id,
        array $data,
        ?UploadedFile $archivo = null
    ): PlanResolucion {
        return DB::transaction(function () use ($id, $data, $archivo) {
            $planResolucion = PlanResolucion::findOrFail($id);

            PlanEstudio::where('id_plan', $planResolucion->id_plan)->update([
                'titulo'      => $data['titulo'],
                'descripcion' => $data['plan_descripcion'] ?? '',
            ]);

            ResolucionCarrera::where('id_resolucion', $planResolucion->id_resolucion)->update([
                'descripcion' => $data['resolucion_descripcion'],
            ]);

            $idTipoArchivo = $planResolucion->id_tipo_archivo;

            if ($archivo) {
                if ($idTipoArchivo) {
                    $tipoArchivoAnterior = TipoArchivo::find($idTipoArchivo);
                    if ($tipoArchivoAnterior?->archivo) {
                        Storage::disk('public')->delete($tipoArchivoAnterior->archivo);
                    }
                }

                $path = $archivo->store('tipos_archivo', 'public');

                if ($idTipoArchivo) {
                    TipoArchivo::where('id_tipo_archivo', $idTipoArchivo)->update([
                        'descripcion' => $data['archivo_descripcion'],
                        'archivo'     => $path,
                    ]);
                } else {
                    $nuevoTipoArchivo = TipoArchivo::create([
                        'descripcion' => $data['archivo_descripcion'],
                        'archivo'     => $path,
                    ]);
                    $idTipoArchivo = $nuevoTipoArchivo->id_tipo_archivo;
                }
            } elseif ($idTipoArchivo) {
                TipoArchivo::where('id_tipo_archivo', $idTipoArchivo)->update([
                    'descripcion' => $data['archivo_descripcion'],
                ]);
            }

            $planResolucion->update([
                'id_carrera'      => $data['id_carrera'] ?? null,
                'total_horas'     => $data['total_horas'],
                'fecha'           => $data['fecha'],
                'id_tipo_archivo' => $idTipoArchivo,
            ]);

            return $this->buscarPorId($planResolucion->id);
        });
    }

   


public function buscarAlumnosPorCarrera(Carrera $carrera, ?string $idMateria = null, ?string $anio = null): Collection
{
    return $carrera->estudiantes()          // pivote estudiante_carrera
        ->with('usuario.dnis')
        ->when($idMateria, function ($q) use ($idMateria) {
            $q->whereHas('materias', fn ($qq) => $qq->where('materias.id_materia', $idMateria));
        })
        ->when($anio, function ($q) use ($anio) {
            $q->whereHas('materias', fn ($qq) => $qq->where('materias.anio', $anio));
        })
        ->get()
        ->sortBy(fn ($e) => $e->usuario->apellido ?? '')
        ->values();
}

    public function buscarEspaciosPorCarrera(Carrera $carrera, ?string $filtro = null): Collection
    {
        return $carrera->materias()
            ->with(['tipoMateria', 'correlativas.materiaCorrelativa'])
            ->when($filtro, fn ($q) => $q->where('nombre', 'like', "%$filtro%"))
            ->orderByDesc('id_ciclo')      // primero el ciclo más reciente
            ->get()
            ->unique('nombre')             // se queda con la versión más reciente
            ->sortBy([['anio', 'asc'], ['nombre', 'asc']])
            ->values();
    }
}