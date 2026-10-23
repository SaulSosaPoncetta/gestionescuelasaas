<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    /**
     * Ids fijos a propósito (antes se generaban random con Rol::factory(),
     * que fue justo la causa del bug del sidebar mostrando el menú
     * equivocado según el usuario logueado).
     *
     * Se usa DB::table() en vez del modelo Rol para no depender de cómo
     * esté (o no esté) declarada la primary key en el modelo Eloquent —
     * inserta/actualiza directo por el nombre real de columna, sin pasar
     * por $fillable ni por el $primaryKey del modelo.
     */
    public function run(): void
    {
        $roles = [
            1 => 'Administrador',
            2 => 'Docente',
            3 => 'Estudiante',
        ];

        foreach ($roles as $id => $descripcion) {
            DB::table('roles')->updateOrInsert(
                ['id_rol' => $id],
                ['descripcion' => $descripcion]
            );
        }
    }
}