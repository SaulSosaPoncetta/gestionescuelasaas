<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MesaExamen;
use App\Models\Docente;
use App\Models\MesaExamenDocente;

class MesaExamenDocenteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mesas = MesaExamen::all();
        $docentes = Docente::all();

        foreach ($mesas as $mesa) {

            // Titular: siempre se asigna.
            $titular = $docentes->random();

            MesaExamenDocente::create([
                'id_mesa'    => $mesa->id_mesa,
                'id_docente' => $titular->id_usuario,
                'cargo'      => 'titular',
            ]);

            // Vocal: opcional, para variar entre mesas con 1 y 2 docentes.
            // Se descarta al titular para no repetir el mismo docente en la
            // misma mesa (mesa_examen_docente tiene PK compuesta id_mesa+id_docente).
            $candidatosVocal = $docentes->where('id_usuario', '!=', $titular->id_usuario);

            if ($candidatosVocal->isNotEmpty() && fake()->boolean(70)) {
                $vocal = $candidatosVocal->random();

                MesaExamenDocente::create([
                    'id_mesa'    => $mesa->id_mesa,
                    'id_docente' => $vocal->id_usuario,
                    'cargo'      => 'vocal',
                ]);
            }
        }
    }
}