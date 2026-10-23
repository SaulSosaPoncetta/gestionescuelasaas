<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Docente;
use App\Models\Administrador;
use App\Models\Usuario;

class DocenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       for ($i = 0; $i < 5; $i++) {

            $usuario = Usuario::factory()->create([
                'id_rol' => 2,
            ]);

            Docente::create([
                'id_usuario' => $usuario->id_usuario,
                'nro_legajo' => fake()->unique()->numerify('LEG####'),
            ]);
        } 
    }
}
