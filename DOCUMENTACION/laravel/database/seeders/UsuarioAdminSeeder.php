<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Administrador;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Rol;
use Illuminate\Support\Facades\Hash;

class UsuarioAdminSeeder extends Seeder
{
    /**
     * IMPORTANTE: este seeder necesita que RolSeeder ya haya corrido antes
     * (RolSeeder crea los 3 roles en orden random via factory, así que acá
     * se busca por 'descripcion' en vez de hardcodear id_rol = 1/2/3).
     *
     * En DatabaseSeeder.php el call() tiene que quedar en este orden:
     *
     *     $this->call([
     *         RolSeeder::class,
     *         UsuarioAdminSeeder::class,
     *     ]);
     */
    public function run(): void
    {
        $idRolAdmin      = Rol::where('descripcion', 'Administrador')->value('id_rol');
        $idRolDocente    = Rol::where('descripcion', 'Docente')->value('id_rol');
        $idRolEstudiante = Rol::where('descripcion', 'Estudiante')->value('id_rol');

        if (!$idRolAdmin || !$idRolDocente || !$idRolEstudiante) {
            $this->command->error(
                'Faltan roles en la tabla roles. Corré RolSeeder antes de UsuarioAdminSeeder.'
            );
            return;
        }

        // -------------------------------------------------------------
        // ADMINISTRADOR
        // -------------------------------------------------------------
        $usuarioAdmin = Usuario::updateOrCreate(
            ['email' => 'admin@a.com'],
            [
                'nombre'         => 'Admin',
                'apellido'       => 'SGI',
                'nombre_usuario' => 'admin',
                'password_hash'  => Hash::make('1234'),
                'id_rol'         => $idRolAdmin,
                'estado'         => 'Activo',
            ]
        );

        // OJO: faltaba esta fila. Docente y Estudiante ya creaban su
        // registro correspondiente en la tabla perfil, pero Administrador
        // no — por eso auth()->id() daba un usuario con rol Administrador
        // que sin embargo no existía en la tabla `administradores`
        // (rompía cualquier validación `exists:administradores,id_usuario`).
        Administrador::updateOrCreate(
            ['id_usuario' => $usuarioAdmin->id_usuario]
        );

        // -------------------------------------------------------------
        // DOCENTE
        // -------------------------------------------------------------
        $usuarioDocente = Usuario::updateOrCreate(
            ['email' => 'doc@a.com'],
            [
                'nombre'         => 'Doc',
                'apellido'       => 'SGI',
                'nombre_usuario' => 'doc',
                'password_hash'  => Hash::make('1234'),
                'id_rol'         => $idRolDocente,
                'estado'         => 'Activo',
            ]
        );

        Docente::updateOrCreate(
            ['id_usuario' => $usuarioDocente->id_usuario],
            ['nro_legajo' => 1000]
        );

        // -------------------------------------------------------------
        // ESTUDIANTE
        // -------------------------------------------------------------
        $usuarioEstudiante = Usuario::updateOrCreate(
            ['email' => 'alu@a.com'],
            [
                'nombre'         => 'Alu',
                'apellido'       => 'SGI',
                'nombre_usuario' => 'alu',
                'password_hash'  => Hash::make('1234'),
                'id_rol'         => $idRolEstudiante,
                'estado'         => 'Activo',
            ]
        );

        Estudiante::updateOrCreate(
            ['id_usuario' => $usuarioEstudiante->id_usuario]
        );
    }
}