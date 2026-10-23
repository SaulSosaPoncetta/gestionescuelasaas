<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiante_carrera', function (Blueprint $table) {
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_carrera');
            $table->date('cohorte');
            $table->unsignedInteger('id_estado_carrera');

            $table->primary(['id_usuario', 'id_carrera', 'cohorte']);
            $table->foreign('id_usuario')->references('id_usuario')->on('estudiantes');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras');
            $table->foreign('id_estado_carrera')->references('id_estado_carrera')->on('estados_carrera');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_carrera');
    }
};
