<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de prueba para alumnos.
 *
 * Crea los datos mínimos necesarios para probar las pantallas
 * de Buscar Alumno y Perfil de Alumno:
 *   - 1 país
 *   - 3 localidades
 *   - 4 roles (Administrador, Secretaria, Docente, Estudiante)
 *   - 3 usuarios estudiantes
 *   - 3 registros en la tabla estudiantes
 *   - DNIs y teléfonos para cada uno
 *
 * Uso:
 *   php artisan db:seed --class=AlumnoTestSeeder
 *
 * Para resetear y volver a seedear desde cero:
 *   php artisan migrate:fresh --seed   (corre DatabaseSeeder)
 *   php artisan db:seed --class=AlumnoTestSeeder   (solo este)
 */
class AlumnoTestSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------
        // País
        // ------------------------------------------------------------------
        $idArgentina = DB::table('paises')->insertGetId([
            'nombre_pais' => 'Argentina',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // ------------------------------------------------------------------
        // Localidades
        // ------------------------------------------------------------------
        $idOlavarria = DB::table('localidades')->insertGetId([
            'id_pais'          => $idArgentina,
            'codigo_postal'    => 7400,
            'nombre_localidad' => 'Olavarría',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $idBsAs = DB::table('localidades')->insertGetId([
            'id_pais'          => $idArgentina,
            'codigo_postal'    => 1000,
            'nombre_localidad' => 'Buenos Aires',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $idRosario = DB::table('localidades')->insertGetId([
            'id_pais'          => $idArgentina,
            'codigo_postal'    => 2000,
            'nombre_localidad' => 'Rosario',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // ------------------------------------------------------------------
        // Roles (solo si no existen)
        // ------------------------------------------------------------------
        $roles = ['Administrador', 'Secretaria', 'Docente', 'Estudiante'];
        foreach ($roles as $desc) {
            $existe = DB::table('roles')->where('descripcion', $desc)->exists();
            if (!$existe) {
                DB::table('roles')->insert([
                    'descripcion' => $desc,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        $idRolEstudiante = DB::table('roles')
            ->where('descripcion', 'Estudiante')
            ->value('id_rol');

        // ------------------------------------------------------------------
        // Usuarios estudiantes
        // ------------------------------------------------------------------
        $alumnos = [
            [
                'nombre'         => 'Juan',
                'apellido'       => 'Pérez',
                'nombre_usuario' => 'jperez',
                'email'          => 'juan.perez@ejemplo.com',
                'dni'            => '30123456',
                'telefono1'      => '2284 328694',
                'telefono2'      => '2284 111222',
                'id_localidad'   => $idOlavarria,
            ],
            [
                'nombre'         => 'María',
                'apellido'       => 'González',
                'nombre_usuario' => 'mgonzalez',
                'email'          => 'maria.gonzalez@ejemplo.com',
                'dni'            => '35678901',
                'telefono1'      => '2284 456789',
                'telefono2'      => null,
                'id_localidad'   => $idBsAs,
            ],
            [
                'nombre'         => 'Carlos',
                'apellido'       => 'Rodríguez',
                'nombre_usuario' => 'crodriguez',
                'email'          => 'carlos.rodriguez@ejemplo.com',
                'dni'            => '28456789',
                'telefono1'      => '341 9876543',
                'telefono2'      => null,
                'id_localidad'   => $idRosario,
            ],
        ];

        foreach ($alumnos as $alumno) {
            // Insertar usuario
            $idUsuario = DB::table('usuarios')->insertGetId([
                'nombre'         => $alumno['nombre'],
                'apellido'       => $alumno['apellido'],
                'nombre_usuario' => $alumno['nombre_usuario'],
                'email'          => $alumno['email'],
                'password_hash'  => Hash::make('password123'),
                'id_rol'         => $idRolEstudiante,
                'id_localidad'   => $alumno['id_localidad'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Insertar en tabla estudiantes
            DB::table('estudiantes')->insert([
                'id_usuario' => $idUsuario,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insertar DNI
            DB::table('dnis')->insert([
                'dni'        => $alumno['dni'],
                'id_usuario' => $idUsuario,
                'tipo_dni'   => 'DNI',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insertar teléfonos
            if ($alumno['telefono1']) {
                DB::table('telefonos')->insert([
                    'telefono'   => $alumno['telefono1'],
                    'id_usuario' => $idUsuario,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if ($alumno['telefono2']) {
                DB::table('telefonos')->insert([
                    'telefono'   => $alumno['telefono2'],
                    'id_usuario' => $idUsuario,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('✓ AlumnoTestSeeder: 3 alumnos de prueba creados.');
        $this->command->info('  Credenciales de cualquier alumno: password = password123');
    }
}
