<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\TipoDni;
use App\Models\Dni;

class DniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Corre después de todos los seeders que crean Usuario
     * (Administrador, Docente, Estudiante, UsuarioAdmin), para que
     * ningún usuario quede sin DNI.
     */
    public function run(): void
    {
        $idsTipoDni = TipoDni::pluck('id_tipo_dni');

        Usuario::whereDoesntHave('dnis')->each(function (Usuario $usuario) use ($idsTipoDni) {
            Dni::create([
                'dni' => fake()->unique()->numberBetween(10000000, 99999999),
                'cuil' => fake()->numerify('20-########-#'),
                'id_usuario' => $usuario->id_usuario,
                'id_tipo_dni' => $idsTipoDni->random(),
            ]);
        });
    }
}