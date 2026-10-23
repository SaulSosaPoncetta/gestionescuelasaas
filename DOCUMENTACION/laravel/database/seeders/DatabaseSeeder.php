<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


// Este archivo centraliza la ejecución de todos los seeders.
// Al correr "php artisan db:seed", Laravel ejecuta este método
// y llama a cada seeder definido en el array.

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([ 
            RolSeeder::class,
            PaisSeeder::class,
            CategoriaAuditoriaSeeder::class, 
            EstadoCarreraSeeder::class,
            TipoEstudianteSeeder::class,
            EstadoEstudianteSeeder::class,
            TipoMateriaSeeder::class,
            TipoMesaExamenSeeder::class,
            TipoArchivoSeeder::class,
            LocalidadSeeder::class,
            // UsuarioSeeder::class,
            TelefonoSeeder::class,
            TipoDniSeeder::class,
            AdministradorSeeder::class,
            DocenteSeeder::class,
            EstudianteSeeder::class,
            // Usuarios fijos de prueba: admin/1234, doc/1234, alu/1234.
            // Va después de los seeders de arriba para no chocar con los
            // administradores/docentes/estudiantes que esos asignan al azar
            // sobre el pool de usuarios ya existente.
            UsuarioAdminSeeder::class,
            DniSeeder::class,
            CarreraSeeder::class,
            ResolucionCarreraSeeder::class,
            PlanEstudioSeeder::class,
            PlanResolucionSeeder::class,
            EstudianteCarreraSeeder::class,
            CicloLectivoSeeder::class,
            MateriaSeeder::class,
            MateriasPorCicloSeeder::class,
            CorrelativaSeeder::class,
            MateriaDocenteSeeder::class,
            ArchivoMateriaSeeder::class,
            DocenteEstudianteSeeder::class,
            LlamadoSeeder::class,
            MesaExamenSeeder::class,
            MesaExamenDocenteSeeder::class,
            ArchivoExamenSeeder::class,
            InscripcionMateriaSeeder::class,
            NotaCuatrimestreSeeder::class,
            NotaFinalSeeder::class,
            AdministracionDocenteSeeder::class,
            AdministracionEstudianteSeeder::class,
            AuditoriaSeeder::class,
            HistorialAcademicoSeeder::class,
            UsuarioAdminSeeder::class,
            EstudianteFinalesSeeder::class,
            EgresadoAluSeeder::class,
            
        ]);
    }
}