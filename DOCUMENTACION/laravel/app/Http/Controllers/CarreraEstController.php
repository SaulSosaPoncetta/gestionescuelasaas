<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EstadoCarrera;
use App\Models\InscripcionMateria;
use App\Models\NotaCuatrimestre;
use App\Models\NotaFinal;

class CarreraEstController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();

        $estudiante = $usuario->estudiante;

        if (!$estudiante) {
            abort(403, 'El usuario no está registrado como estudiante.');
        }

        // ---------------------------------------------------------
        // CARRERAS DEL ESTUDIANTE
        // ---------------------------------------------------------

        $carreras = $estudiante->carreras()
            ->with([
                'planVigente.plan',
                'materias',
            ])
            ->get();


        foreach ($carreras as $carrera) {

            // ---------------------------------------------------------
            // ÚLTIMA MATRICULACIÓN
            // ---------------------------------------------------------

            $ultimaMatriculacion = InscripcionMateria::where(
                'id_usuario',
                $estudiante->id_usuario
            )
                ->whereHas('materia', function ($query) use ($carrera) {
                    $query->where('id_carrera', $carrera->id_carrera);
                })
                ->max('fecha_inscripcion');

            $carrera->ultima_matriculacion = $ultimaMatriculacion;


            // ---------------------------------------------------------
            // ESTADO DE LA CARRERA
            // ---------------------------------------------------------

            $estadoCarrera = EstadoCarrera::find(
                $carrera->pivot->id_estado_carrera
            );
            $carrera->estado_carrera = $estadoCarrera?->estado;

            // Se recibió si la carrera figura como Egresado (lo marca
            // MateriaService::registrarEgresoSiCorresponde al llegar al
            // 100% del analítico) o como Finalizada.
            $carrera->se_recibio = in_array(
                $carrera->estado_carrera,
                ['Egresado', 'Finalizada'],
                true
            );

            // ---------------------------------------------------------
            // MATERIAS DE ESTA CARRERA
            // ---------------------------------------------------------

            $idsMaterias = $carrera->materias
                ->pluck('id_materia');

            // ---------------------------------------------------------
            // NOTAS FINALES
            // ---------------------------------------------------------

            $notasFinales = NotaFinal::where(
                'id_usuario',
                $estudiante->id_usuario
            )
                ->whereIn('id_materia', $idsMaterias)
                ->get()
                ->groupBy('id_materia');


            // ---------------------------------------------------------
            // NOTAS DE CIERRE DE CURSADA
            // ---------------------------------------------------------

            $notasCursada = NotaCuatrimestre::where(
                'id_usuario',
                $estudiante->id_usuario
            )
                ->whereIn('id_materia', $idsMaterias)
                ->where('periodo', 'CIERRE')
                ->get()
                ->keyBy('id_materia');


            // ---------------------------------------------------------
            // CALCULAR PROMEDIO
            // ---------------------------------------------------------

            $notasParaPromedio = [];
            foreach ($idsMaterias as $idMateria) {
                // -----------------------------------------------------
                // Si tiene nota final, usamos la nota final
                // -----------------------------------------------------
                if (isset($notasFinales[$idMateria])) {

                    $notaFinal = $notasFinales[$idMateria]
                        ->sortByDesc('fecha_carga')
                        ->first();

                    if ($notaFinal->valor !== null) {
                        $notasParaPromedio[] =
                            (float) $notaFinal->valor;
                    }

                    continue;
                }
                // -----------------------------------------------------
                // Si no tiene final, buscamos el cierre de cursada
                // -----------------------------------------------------
                if (isset($notasCursada[$idMateria])) {
                    $notaCierre = $notasCursada[$idMateria];
                    if (
                        in_array(
                            $notaCierre->situacion,
                            [
                                'aprobado',
                                'promocionado',
                                'promocion_directa',
                            ],
                            true
                        )
                        && $notaCierre->valor !== null
                    ) {
                        $notasParaPromedio[] =
                            (float) $notaCierre->valor;
                    }
                }
            }

            // ---------------------------------------------------------
            // PROMEDIO FINAL
            // ---------------------------------------------------------
            if (count($notasParaPromedio) > 0) {
                $promedio =
                    array_sum($notasParaPromedio)
                    / count($notasParaPromedio);
                $carrera->promedio = number_format(
                    round($promedio, 2),
                    2,
                    ',',
                    '.'
                );
            } else {
                $carrera->promedio = 'Sin datos';
            }
        }
        return view(
            'Estudiante.carreraEst',
            compact(
                'usuario',
                'estudiante',
                'carreras'
            )
        );
    }
}