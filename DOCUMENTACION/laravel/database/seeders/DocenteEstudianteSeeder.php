<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocenteEstudiante;
use App\Models\Docente;
use App\Models\Estudiante;


class DocenteEstudianteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $docentes = Docente::all();
        $estudiantes = Estudiante::all();

        foreach ($estudiantes as $estudiante) {

            $docente = $docentes->random();

            DocenteEstudiante::create([
                'id_usuario_docente' => $docente->id_usuario,
                'id_usuario_estudiante' => $estudiante->id_usuario,
                'fecha_hora' => fake()->dateTimeBetween('-1 year', 'now'),
            ]);
        }
    }
}
