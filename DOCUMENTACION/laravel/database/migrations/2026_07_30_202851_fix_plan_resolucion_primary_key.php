<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Quitar temporalmente las FKs que dependen de la PK compuesta
        Schema::table('plan_resolucion', function (Blueprint $table) {
            $table->dropForeign(['id_resolucion']);
            $table->dropForeign(['id_plan']);
            $table->dropForeign(['id_carrera']);
        });

        // 2. Quitar la PK antigua y agregar el ID autoincremental
        Schema::table('plan_resolucion', function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table('plan_resolucion', function (Blueprint $table) {
            $table->id()->first();
        });

        // 3. Crear el índice único y restaurar las FKs
        Schema::table('plan_resolucion', function (Blueprint $table) {
            $table->unique(['id_resolucion', 'id_plan', 'id_carrera'], 'plan_resolucion_unico');

            $table->foreign('id_resolucion')->references('id_resolucion')->on('resoluciones_carrera');
            $table->foreign('id_plan')->references('id_plan')->on('planes_estudio');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras');
            // NOTA: id_tipo_archivo SE OMITE aquí porque ya existía desde el create original
        });
    }

    public function down(): void
    {
        Schema::table('plan_resolucion', function (Blueprint $table) {
            $table->dropForeign(['id_resolucion']);
            $table->dropForeign(['id_plan']);
            $table->dropForeign(['id_carrera']);
            $table->dropUnique('plan_resolucion_unico');
            $table->dropColumn('id');
        });

        Schema::table('plan_resolucion', function (Blueprint $table) {
            $table->primary(['id_resolucion', 'id_plan', 'id_carrera']);
            $table->foreign('id_resolucion')->references('id_resolucion')->on('resoluciones_carrera');
            $table->foreign('id_plan')->references('id_plan')->on('planes_estudio');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras');
        });
    }
};