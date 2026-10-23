<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CicloLectivo;

class CicloLectivoSeeder extends Seeder
{
    public function run(): void
    {
    CicloLectivo::factory()->paraAnios(2020, 2026)->create();   
    }
}