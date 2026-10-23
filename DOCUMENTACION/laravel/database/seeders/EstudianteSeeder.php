<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Estudiante;
use App\Models\Usuario;
use App\Models\Administrador;
use App\Models\Docente;

class EstudianteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 20; $i++) {

            $usuario = Usuario::factory()->create([
                'id_rol' => 3,
            ]);

            Estudiante::create([
                'id_usuario' => $usuario->id_usuario,
            ]);
        }
    }
}
