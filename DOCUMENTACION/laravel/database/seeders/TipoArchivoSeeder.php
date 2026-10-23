<?php

namespace Database\Seeders;
use App\Models\TipoArchivo;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoArchivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipoArchivo::factory()->count(5)->create();
    }
}
