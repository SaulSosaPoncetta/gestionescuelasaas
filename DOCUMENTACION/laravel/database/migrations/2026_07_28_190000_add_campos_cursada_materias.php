<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completa columnas que las pantallas de Cursada (materiaAgregar,
     * materiaPerfil, docMatUpload, cargarNota) ya esperaban pero que
     * todavía no existían en el esquema.
     */
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->unsignedInteger('id_plan')->nullable()->after('id_carrera');
            $table->unsignedTinyInteger('orden_anio')->nullable()->after('anio');
            $table->unsignedInteger('carga_horaria_anual')->nullable()->after('orden_anio');
            $table->boolean('tfo_edi_udi')->default(false)->after('carga_horaria_anual');
            $table->boolean('acreditable_final')->default(true)->after('tfo_edi_udi');
            $table->boolean('promocionable')->default(true)->after('acreditable_final');
            $table->boolean('optativa')->default(false)->after('promocionable');

            $table->foreign('id_plan')->references('id_plan')->on('planes_estudio');
        });

        Schema::table('materia_docente', function (Blueprint $table) {
            $table->unsignedInteger('division')->nullable()->after('situacion_revista');
            $table->date('fecha_inicio')->nullable()->after('division');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->unsignedInteger('horas_semanales')->nullable()->after('fecha_fin');
        });

        Schema::table('notas_cuatrimestre', function (Blueprint $table) {
            $table->unsignedInteger('id_ciclo')->nullable()->after('id_materia');
            $table->string('periodo', 10)->nullable()->after('id_ciclo'); // 1C | 2C | REC | CIERRE
            $table->string('situacion', 20)->nullable()->after('periodo'); // promocionado | aprobado | desaprobado

            $table->foreign('id_ciclo')->references('id_ciclo')->on('ciclos_lectivos');
        });
    }

    public function down(): void
    {
        Schema::table('notas_cuatrimestre', function (Blueprint $table) {
            $table->dropForeign(['id_ciclo']);
            $table->dropColumn(['id_ciclo', 'periodo', 'situacion']);
        });

        Schema::table('materia_docente', function (Blueprint $table) {
            $table->dropColumn(['division', 'fecha_inicio', 'fecha_fin', 'horas_semanales']);
        });

        Schema::table('materias', function (Blueprint $table) {
            $table->dropForeign(['id_plan']);
            $table->dropColumn([
                'id_plan', 'orden_anio', 'carga_horaria_anual',
                'tfo_edi_udi', 'acreditable_final', 'promocionable', 'optativa',
            ]);
        });
    }
};
