<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ArchivoExamen;
use App\Models\TipoArchivo;
use App\Models\Usuario;
use App\Models\MesaExamen;

class ArchivoExamenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposArchivo = TipoArchivo::all();
        $mesas = MesaExamen::all();

        $usuarios = Usuario::inRandomOrder()
            ->take(20)
            ->get();

        foreach ($usuarios as $usuario) {

            $tipoArchivo = $tiposArchivo->random();

            ArchivoExamen::create([
                'id_tipo_archivo' => $tipoArchivo->id_tipo_archivo,
                'id_usuario' => $usuario->id_usuario,
                'id_mesa' => $mesas->random()->id_mesa,
            ]);
        }
    }
}
