<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InscripcionMateria;
use App\Models\Estudiante;
use App\Models\Materia;
use App\Models\CicloLectivo;
use App\Models\TipoEstudiante;
use App\Models\EstadoEstudiante;

class InscripcionMateriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $materias = Materia::all();

    $ciclo = CicloLectivo::where('fecha_fin', '>=', now())
        ->orderBy('fecha_inicio')
        ->first();

    $tipos = TipoEstudiante::all();
    $estados = EstadoEstudiante::all();
    $estudiantes = Estudiante::all();

    if (!$ciclo) {
        $this->command->error('No existe un ciclo lectivo activo.');
        return;
    }

    foreach ($estudiantes as $estudiante) {

        $cantidadMaterias = rand(3, 6);

        $materiasAsignadas = $materias->random(
            min($cantidadMaterias, $materias->count())
        );

        foreach ($materiasAsignadas as $materia) {

            InscripcionMateria::create([
                'id_usuario' => $estudiante->id_usuario,
                'id_materia' => $materia->id_materia,
                'id_ciclo' => $ciclo->id_ciclo,

                'fecha_inscripcion' => fake()->dateTimeBetween(
                    '-2 years',
                    'now'
                ),

                'id_tipo_estudiante' =>
                    $tipos->random()->id_tipo_estudiante,

                'id_estado_estudiante' =>
                    $estados->random()->id_estado_estudiante,

                'contador_finales' => rand(0, 5),

                'fecha_vencimiento' => fake()->dateTimeBetween(
                    'now',
                    '+2 years'
                ),

                'libro' => 'L-' . rand(1, 20),
                'folio' => 'F-' . rand(1, 500),
                'division' => rand(1, 5),
            ]);
        }
    }
}
}
