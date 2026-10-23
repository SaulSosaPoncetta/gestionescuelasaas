<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArgentinaGeoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Insertar Argentina en paises (si no existe)
        $existePais = DB::table('paises')->where('nombre_pais', 'Argentina')->exists();

        if (!$existePais) {
            DB::table('paises')->insert([
                'nombre_pais' => 'Argentina',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        $idArgentina = DB::table('paises')->where('nombre_pais', 'Argentina')->value('id_pais');
        $this->command->info("✓ País Argentina (id: $idArgentina)");

        // 2. Leer el archivo JSON local
        $archivo = database_path('seeders/municipios.json');

        if (!file_exists($archivo)) {
            $this->command->error('No se encontró database/seeders/municipios.json');
            return;
        }

        $json       = file_get_contents($archivo);
        $datos      = json_decode($json, true);
        $municipios = $datos['municipios'] ?? [];

        if (empty($municipios)) {
            $this->command->error('El archivo JSON está vacío o mal formateado.');
            return;
        }

        $this->command->info('Municipios en archivo: ' . count($municipios));

        // 3. Insertar en lotes de 200
        $existentes = DB::table('localidades')
            ->where('id_pais', $idArgentina)
            ->pluck('nombre_localidad')
            ->map(fn($n) => strtolower($n))
            ->toArray();

        $insertar = [];
        $ahora    = now()->toDateTimeString();

        foreach ($municipios as $municipio) {
            $nombre = trim($municipio['nombre'] ?? '');

            if (!$nombre) continue;
            if (in_array(strtolower($nombre), $existentes)) continue;

            $existentes[] = strtolower($nombre);

            $insertar[] = [
                'id_pais'          => $idArgentina,
                'nombre_localidad' => $nombre,
                'codigo_postal'    => 0,
                'created_at'       => $ahora,
                'updated_at'       => $ahora,
            ];

            if (count($insertar) >= 200) {
                DB::table('localidades')->insert($insertar);
                $insertar = [];
            }
        }

        if (!empty($insertar)) {
            DB::table('localidades')->insert($insertar);
        }

        $total = DB::table('localidades')->where('id_pais', $idArgentina)->count();
        $this->command->info("✓ Localidades cargadas: $total");
        $this->command->info('¡Listo!');
    }
}
