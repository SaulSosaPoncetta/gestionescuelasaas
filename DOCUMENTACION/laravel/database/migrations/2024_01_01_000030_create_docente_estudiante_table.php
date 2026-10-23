<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docente_estudiante', function (Blueprint $table) {
            $table->unsignedInteger('id_usuario_docente');
            $table->unsignedInteger('id_usuario_estudiante');
            $table->dateTime('fecha_hora');

            $table->primary(['id_usuario_docente', 'id_usuario_estudiante', 'fecha_hora']);
            $table->foreign('id_usuario_docente')->references('id_usuario')->on('docentes');
            $table->foreign('id_usuario_estudiante')->references('id_usuario')->on('estudiantes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_estudiante');
    }
};
