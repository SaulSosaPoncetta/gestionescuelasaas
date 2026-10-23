<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_resolucion', function (Blueprint $table) {
            $table->unsignedInteger('id_resolucion');
            $table->unsignedInteger('id_plan');
            $table->unsignedInteger('id_carrera');
            $table->unsignedInteger('id_tipo_archivo')->nullable();
            $table->integer('total_horas')->nullable();
            $table->date('fecha')->nullable();

            $table->primary(['id_resolucion', 'id_plan', 'id_carrera']);
            $table->foreign('id_resolucion')->references('id_resolucion')->on('resoluciones_carrera');
            $table->foreign('id_plan')->references('id_plan')->on('planes_estudio');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras');
            $table->foreign('id_tipo_archivo')->references('id_tipo_archivo')->on('tipos_archivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_resolucion');
    }
};
