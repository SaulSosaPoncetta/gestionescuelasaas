<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PlanResolucion;
use App\Models\ResolucionCarrera;
use App\Models\PlanEstudio;
use App\Models\Carrera;
use App\Models\TipoArchivo;

class PlanResolucionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resoluciones = ResolucionCarrera::all();
        $planes = PlanEstudio::all();
        $carreras = Carrera::all();
        $tiposArchivo = TipoArchivo::all();

        $cantidad = min(
            $resoluciones->count(),
            $planes->count(),
            $carreras->count()
        );

        for ($i = 0; $i < $cantidad; $i++) {
            PlanResolucion::create([
                'id_resolucion' => $resoluciones[$i]->id_resolucion,
                'id_plan' => $planes[$i]->id_plan,
                'id_carrera' => $carreras[$i]->id_carrera,
                'id_tipo_archivo' => $tiposArchivo->random()->id_tipo_archivo,
                'total_horas' => fake()->numberBetween(1500, 3000),
                'fecha' => fake()->date(),
            ]);
        }
    }
}
