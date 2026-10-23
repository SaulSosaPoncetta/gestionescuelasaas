<?php

namespace Database\Seeders;

use App\Models\Llamado;
use Illuminate\Database\Seeder;

class LlamadoSeeder extends Seeder
{
    /**
     * Llamados genéricos (períodos), sin atarlos a un año/ciclo particular.
     * El año de una mesa concreta sale de mesas_examen.id_ciclo.
     *
     * 'meses_validos' = null significa "sin restricción de mes": se usa
     * para llamados que no dependen de un período fijo, como
     * "Extracurricular".
     */
    public function run(): void
    {
        collect([
            'Febrero/Marzo'        => [2, 3],
            'Julio/Agosto'         => [7, 8],
            'Noviembre/Diciembre'  => [11, 12],
            'Extracurricular'      => null,
        ])->each(fn ($meses, $descripcion) => Llamado::firstOrCreate(
            ['descripcion' => $descripcion],
            ['meses_validos' => $meses]
        ));
    }
}