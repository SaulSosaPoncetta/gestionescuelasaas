<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ArchivoMateria;
use App\Models\Materia;
use App\Models\TipoArchivo;

class ArchivoMateriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materias = Materia::all();
        $tiposArchivo = TipoArchivo::all();

        foreach ($materias as $materia) {

            $tipoArchivo = $tiposArchivo->random();

            ArchivoMateria::create([
                'id_tipo_archivo' => $tipoArchivo->id_tipo_archivo,
                'id_materia' => $materia->id_materia,
            ]);
        }
    }
}
