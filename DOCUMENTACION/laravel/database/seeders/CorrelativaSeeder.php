<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Correlativa;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class CorrelativaSeeder extends Seeder
{
    /**
     * Genera correlativas de CURSADA con sentido curricular:
     * para cursar una materia de año N, hace falta tener aprobadas TODAS
     * las materias de año N-1 de la MISMA carrera.
     *
     * SUPUESTO: esta es la regla que asumo en base a lo que me describiste
     * ("si apruebo una en 1er año, el próximo puedo cursar las que sean
     * correlativas a esa, y las que no tengan correlativas"). Si en tu plan
     * de estudios real una materia de año 3 depende de UNA materia puntual
     * de año 1 (no de "todo" año 2), avisame y cambio este seeder para leer
     * esas relaciones puntuales en vez de generarlas por año completo.
     *
     * No generamos `correlativa_final` acá (no tengo definida esa regla
     * todavía) — queda en false/null salvo que la cargues aparte.
     *
     * A diferencia del seeder anterior, NUNCA cruza materias de carreras
     * distintas: el random original (`Materia::all()->random()`) podía
     * asignarle a una materia una correlativa de otra carrera, lo cual no
     * tiene sentido académico.
     */
    public function run(): void
    {
        Correlativa::query()->delete();

        Carrera::all()->each(function (Carrera $carrera) {
            $materiasPorAnio = Materia::where('id_carrera', $carrera->id_carrera)
                ->get()
                ->groupBy('anio')
                ->sortKeys(); // año 1, 2, 3...

            $anios = $materiasPorAnio->keys()->all();

            foreach ($anios as $indice => $anio) {
                if ($indice === 0) {
                    continue; // primer año de la carrera: sin correlativas
                }

                $anioAnterior = $anios[$indice - 1];
                $materiasAnioAnterior = $materiasPorAnio->get($anioAnterior);
                $materiasEsteAnio = $materiasPorAnio->get($anio);

                foreach ($materiasEsteAnio as $materia) {
                    foreach ($materiasAnioAnterior as $correlativa) {
                        Correlativa::create([
                            'id_materia'             => $materia->id_materia,
                            'id_materia_correlativa' => $correlativa->id_materia,
                            'correlativa_cursada'    => true,
                            'correlativa_final'      => true,
                            'descripcion'            => "Para cursar {$materia->nombre} hay que tener aprobada la cursada de {$correlativa->nombre}.",
                        ]);
                    }
                }
            }
        });

        $this->command->info('Correlativas generadas por año/carrera (reemplazando las aleatorias).');
    }
}