<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Administrador;
use App\Models\Usuario;

class AdministradorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 5; $i++) {

            $usuario = Usuario::factory()->create([
                'id_rol' => 1,
            ]);

            Administrador::create([
                'id_usuario' => $usuario->id_usuario,
            ]);
        }  
    }
}
