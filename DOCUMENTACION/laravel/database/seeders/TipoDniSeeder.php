<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TipoDni;

class TipoDniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipoDni::create(['descripcion' => 'DNI']);
        TipoDni::create(['descripcion' => 'LC']);
        TipoDni::create(['descripcion' => 'LE']);
    }
}
