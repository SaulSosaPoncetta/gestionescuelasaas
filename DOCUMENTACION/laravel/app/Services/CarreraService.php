<?php

namespace App\Services;

use App\Models\Carrera;
use App\Models\PlanResolucion;
use App\Models\ResolucionCarrera;
use App\Models\PlanEstudio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Models\TipoArchivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

 
class CarreraService
{
    public function buscar(array $filtros): LengthAwarePaginator
    {
        $idSinAsignar = Carrera::sinAsignar()->id_carrera;

        $query = Carrera::query()
            ->where('id_carrera', '!=', $idSinAsignar)
            ->with(['planResolucion.plan', 'planResolucion.resolucion']);

        if (!empty($filtros['nombre'])) {
            $busqueda = '%' . $filtros['nombre'] . '%';
            $query->where(function ($q) use ($busqueda) {
                $q->where('nombre', 'like', $busqueda)
                ->orWhere('titulo', 'like', $busqueda);
            });
        }

        $carreras = $query->orderBy('nombre')->get();

    // Una fila por cada carrera + plan_resolucion.
    // Si la carrera no tiene ningún plan, se muestra una sola fila con plan null (como "Futbolero").
    $filas = collect();

    foreach ($carreras as $carrera) {
        if ($carrera->planResolucion->isEmpty()) {
            $filas->push((object) [
                'id_carrera'     => $carrera->id_carrera,
                'nombre'         => $carrera->nombre,
                'titulo'         => $carrera->titulo,
                'planResolucion' => null,
            ]);
            continue;
        }

        foreach ($carrera->planResolucion as $pr) {
            $filas->push((object) [
                'id_carrera'     => $carrera->id_carrera,
                'nombre'         => $carrera->nombre,
                'titulo'         => $carrera->titulo,
                'planResolucion' => $pr,
            ]);
        }
    }

    // Paginación manual, ya que la "tabla" real ahora es la colección aplanada
    $perPage = 15;
    $page    = LengthAwarePaginator::resolveCurrentPage();
    $items   = $filas->slice(($page - 1) * $perPage, $perPage)->values();

    return new LengthAwarePaginator(
        $items,
        $filas->count(),
        $perPage,
        $page,
        ['path' => LengthAwarePaginator::resolveCurrentPath()]
    );
}

    public function getById(int $id): Carrera
    {
        return Carrera::with(['planResolucion.plan', 'planResolucion.resolucion'])
            ->findOrFail($id);
    }
    /**
     * Crea la carrera y la vincula a un plan de estudio + resolución
     * existentes a través de la tabla puente plan_resolucion.
     *
     * id_tipo_archivo, total_horas y fecha quedan en null: se completan
     * después desde la pantalla de "crear plan de estudio".
     *
     * $data espera: nombre, titulo, id_plan, id_resolucion.
     */
    public function create(array $data): Carrera
{
    return DB::transaction(function () use ($data) {
        $carrera = Carrera::create([
            'nombre' => $data['nombre'],
            'titulo' => $data['titulo'],
        ]);

        if (!empty($data['id_plan']) && !empty($data['id_resolucion'])) {
            $idSinAsignar = Carrera::sinAsignar()->id_carrera;

            $planResolucion = PlanResolucion::with(['plan', 'resolucion'])
                ->where('id_plan', $data['id_plan'])
                ->where('id_resolucion', $data['id_resolucion'])
                ->first();

            if (!$planResolucion) {
                // combinación nueva, sin fila previa
                PlanResolucion::create([
                    'id_carrera'      => $carrera->id_carrera,
                    'id_plan'         => $data['id_plan'],
                    'id_resolucion'   => $data['id_resolucion'],
                    'id_tipo_archivo' => null,
                    'total_horas'     => null,
                    'fecha'           => null,
                ]);
            } elseif ($planResolucion->id_carrera == $idSinAsignar) {
                // estaba "sin asignar" -> la reasignamos a la carrera nueva
                $planResolucion->update(['id_carrera' => $carrera->id_carrera]);
            } else {
                // ya pertenece a otra carrera real -> duplicamos con los mismos datos
                $nuevoPlan = PlanEstudio::create([
                    'titulo'      => $planResolucion->plan->titulo,
                    'descripcion' => $planResolucion->plan->descripcion,
                ]);

                $nuevaResolucion = ResolucionCarrera::create([
                    'descripcion' => $planResolucion->resolucion->descripcion,
                ]);

                PlanResolucion::create([
                    'id_carrera'      => $carrera->id_carrera,
                    'id_plan'         => $nuevoPlan->id_plan,
                    'id_resolucion'   => $nuevaResolucion->id_resolucion,
                    'id_tipo_archivo' => $planResolucion->id_tipo_archivo,
                    'total_horas'     => $planResolucion->total_horas,
                    'fecha'           => $planResolucion->fecha,
                ]);
            }
        }

        return $carrera->load('planResolucion.plan', 'planResolucion.resolucion');
    });
}
    public function completarPlanEstudio(int $idCarrera, array $data, UploadedFile $archivo): PlanResolucion
    {
        return DB::transaction(function () use ($idCarrera, $data, $archivo) {
            $planResolucion = PlanResolucion::where('id_carrera', $idCarrera)
                ->where('id_plan', $data['id_plan'])
                ->where('id_resolucion', $data['id_resolucion'])
                ->firstOrFail();

            // Si ya había un tipo de archivo cargado antes en este plan, borramos el archivo físico viejo
            if ($planResolucion->id_tipo_archivo) {
                $tipoArchivoAnterior = TipoArchivo::find($planResolucion->id_tipo_archivo);
                if ($tipoArchivoAnterior?->archivo) {
                    Storage::disk('public')->delete($tipoArchivoAnterior->archivo);
                }
            }

            $path = $archivo->store('tipos_archivo', 'public');

            $tipoArchivo = TipoArchivo::create([
                'descripcion' => $data['descripcion'],
                'archivo'     => $path,
            ]);

            PlanResolucion::where('id_carrera', $idCarrera)
            ->where('id_plan', $data['id_plan'])
            ->where('id_resolucion', $data['id_resolucion'])
            ->update([
                'id_tipo_archivo' => $tipoArchivo->id_tipo_archivo,
                'total_horas'     => $data['total_horas'],
                'fecha'           => $data['fecha'],
            ]);

            return $planResolucion->fresh(['plan', 'resolucion', 'tipoArchivo']);
        });
    }

   public function update(int $id, array $data): Carrera
    {
        return DB::transaction(function () use ($id, $data) {
            $carrera = Carrera::findOrFail($id);

            $carrera->update([
                'nombre' => $data['nombre'],
                'titulo' => $data['titulo'],
            ]);

            if (!empty($data['id_plan']) && !empty($data['id_resolucion'])) {
                $idSinAsignar = Carrera::sinAsignar()->id_carrera;

                $actual = PlanResolucion::where('id_carrera', $id)->first();

                $yaExisteIgual = $actual
                    && $actual->id_plan == $data['id_plan']
                    && $actual->id_resolucion == $data['id_resolucion'];

                if (!$yaExisteIgual) {
                    $planResolucionElegido = PlanResolucion::with(['plan', 'resolucion'])
                        ->where('id_plan', $data['id_plan'])
                        ->where('id_resolucion', $data['id_resolucion'])
                        ->first();

                    if (!$planResolucionElegido) {
                        // combinación nueva, sin fila previa: reutilizamos la fila
                        // que la carrera ya tenía (o creamos una si no tenía)
                        if ($actual) {
                            $actual->update([
                                'id_plan'       => $data['id_plan'],
                                'id_resolucion' => $data['id_resolucion'],
                            ]);
                        } else {
                            PlanResolucion::create([
                                'id_carrera'      => $id,
                                'id_plan'         => $data['id_plan'],
                                'id_resolucion'   => $data['id_resolucion'],
                                'id_tipo_archivo' => null,
                                'total_horas'     => null,
                                'fecha'           => null,
                            ]);
                        }
                    } elseif ($planResolucionElegido->id_carrera == $idSinAsignar) {
                        // estaba "sin asignar" -> lo reasignamos a esta carrera
                        $planResolucionElegido->update(['id_carrera' => $id]);

                        // si la carrera tenía otro plan antes, esa fila vieja se borra
                        if ($actual) {
                            $actual->delete();
                        }
                    } elseif ($planResolucionElegido->id_carrera != $id) {
                        // ya pertenece a otra carrera real -> duplicamos
                        $nuevoPlan = PlanEstudio::create([
                            'titulo'      => $planResolucionElegido->plan->titulo,
                            'descripcion' => $planResolucionElegido->plan->descripcion,
                        ]);

                        $nuevaResolucion = ResolucionCarrera::create([
                            'descripcion' => $planResolucionElegido->resolucion->descripcion,
                        ]);

                        if ($actual) {
                            $actual->update([
                                'id_plan'         => $nuevoPlan->id_plan,
                                'id_resolucion'   => $nuevaResolucion->id_resolucion,
                                'id_tipo_archivo' => $planResolucionElegido->id_tipo_archivo,
                                'total_horas'     => $planResolucionElegido->total_horas,
                                'fecha'           => $planResolucionElegido->fecha,
                            ]);
                        } else {
                            PlanResolucion::create([
                                'id_carrera'      => $id,
                                'id_plan'         => $nuevoPlan->id_plan,
                                'id_resolucion'   => $nuevaResolucion->id_resolucion,
                                'id_tipo_archivo' => $planResolucionElegido->id_tipo_archivo,
                                'total_horas'     => $planResolucionElegido->total_horas,
                                'fecha'           => $planResolucionElegido->fecha,
                            ]);
                        }
                    }
                    // si $planResolucionElegido->id_carrera == $id, ya es de esta
                    // carrera (cubierto por $yaExisteIgual), no debería llegar acá
                }
            }

            return $carrera->fresh(['planResolucion.plan', 'planResolucion.resolucion']);
        });
    }
}