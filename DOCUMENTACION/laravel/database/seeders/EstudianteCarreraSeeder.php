<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EstudianteCarrera;
use App\Models\Estudiante;
use App\Models\Carrera;
use App\Models\EstadoCarrera;

class EstudianteCarreraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estudiantes = Estudiante::all();
        $carreras = Carrera::all();
        $estados = EstadoCarrera::all();

        foreach ($estudiantes as $estudiante) {
            EstudianteCarrera::create([
                'id_usuario' => $estudiante->id_usuario,
                'id_carrera' => $carreras->random()->id_carrera,
                'cohorte' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                'id_estado_carrera' => $estados->random()->id_estado_carrera,
            ]);
        }
    }
}
