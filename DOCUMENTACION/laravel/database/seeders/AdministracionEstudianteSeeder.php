<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AdministracionEstudiante;
use App\Models\Administrador;
use App\Models\Estudiante;

class AdministracionEstudianteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administradores = Administrador::all();
        $estudiantes = Estudiante::all();

        foreach ($estudiantes as $estudiante) {

            AdministracionEstudiante::create([
                'id_usuario_administracion' =>
                    $administradores->random()->id_usuario,

                'id_usuario_estudiante' =>
                    $estudiante->id_usuario,

                'fecha_hora' =>
                    fake()->dateTimeBetween('-1 year', 'now'),
            ]);
        }
    }
}
