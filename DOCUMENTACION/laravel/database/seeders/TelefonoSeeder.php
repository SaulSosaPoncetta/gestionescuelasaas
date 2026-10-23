<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Telefono;

class TelefonoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Usuario::all() as $usuario) {

            Telefono::create([
                'telefono' => fake()->numerify('11########'),
                'id_usuario' => $usuario->id_usuario,
            ]);

        }
    }
}
