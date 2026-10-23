<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones_materia', function (Blueprint $table) {
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_materia');
            $table->unsignedInteger('id_ciclo');
            $table->date('fecha_inscripcion')->nullable();
            $table->unsignedInteger('id_tipo_estudiante');
            $table->unsignedInteger('id_estado_estudiante');
            $table->integer('contador_finales')->default(0);
            $table->date('fecha_vencimiento')->nullable();
            $table->string('libro')->nullable();
            $table->string('folio')->nullable();
            $table->integer('division')->nullable();

            // PK compuesta incluye id_ciclo: un alumno puede cursar
            // la misma materia en distintos ciclos lectivos
            $table->primary(['id_usuario', 'id_materia', 'id_ciclo']);
            $table->foreign('id_usuario')->references('id_usuario')->on('estudiantes');
            $table->foreign('id_materia')->references('id_materia')->on('materias');
            $table->foreign('id_ciclo')->references('id_ciclo')->on('ciclos_lectivos');
            $table->foreign('id_tipo_estudiante')->references('id_tipo_estudiante')->on('tipos_estudiante');
            $table->foreign('id_estado_estudiante')->references('id_estado_estudiante')->on('estados_estudiante');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones_materia');
    }
};
