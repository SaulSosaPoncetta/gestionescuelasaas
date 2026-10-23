<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MateriaDocente;
use App\Models\Materia;
use App\Models\Docente;

class MateriaDocenteSeeder extends Seeder
{
    public function run(): void
    {
        $materias = Materia::all();
        $docentes = Docente::all();

        foreach ($materias as $materia) {

            $docente = $docentes->random();

            // El id_ciclo de la asignación TIENE que ser el mismo ciclo al
            // que pertenece la fila de Materia, no uno elegido al azar. Antes
            // se tomaba $ciclos->random() de forma independiente, así que
            // una Materia de 2021 podía quedar asignada con id_ciclo = 2026
            // (el ciclo abierto), y eso hacía que apareciera mezclada en
            // "Cursadas" (que filtra por materia_docente.id_ciclo) como si
            // fuera del ciclo actual.
            MateriaDocente::create([
                'id_materia'          => $materia->id_materia,
                'id_usuario_docente'  => $docente->id_usuario,
                'id_usuario'          => $docente->id_usuario,
                'id_ciclo'            => $materia->id_ciclo,
                'situacion_revista'   => fake()->randomElement([
                    'Titular',
                    'Adjunto',
                    'JTP',
                    'Auxiliar',
                ]),
            ]);
        }
    }
}