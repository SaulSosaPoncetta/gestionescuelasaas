<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AdministracionDocente;
use App\Models\Administrador;
use App\Models\Docente;

class AdministracionDocenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administradores = Administrador::all();
        $docentes = Docente::all();

        foreach ($docentes as $docente) {

            AdministracionDocente::create([
                'id_usuario_administracion' =>
                    $administradores->random()->id_usuario,

                'id_usuario_docente' =>
                    $docente->id_usuario,

                'fecha_hora' =>
                    fake()->dateTimeBetween('-1 year', 'now'),
            ]);
        }
    }
}
