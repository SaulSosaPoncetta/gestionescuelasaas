<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->increments('id_materia');
            $table->unsignedInteger('id_tipo_materia');
            $table->decimal('nota_promocionable', 5, 2)->nullable();
            $table->decimal('nota_aprobada', 5, 2)->nullable();
            $table->string('nombre');
            $table->smallInteger('anio')->nullable(); // año lectivo ej: 2024
            $table->unsignedInteger('id_usuario_administrador')->nullable();
            $table->unsignedInteger('id_carrera');
            $table->unsignedInteger('id_ciclo')->nullable(); 
            $table->timestamps();

            $table->foreign('id_tipo_materia')->references('id_tipo_materia')->on('tipos_materia');
            $table->foreign('id_usuario_administrador')->references('id_usuario')->on('administradores');
            $table->foreign('id_carrera')->references('id_carrera')->on('carreras');
            $table->foreign('id_ciclo')->references('id_ciclo')->on('ciclos_lectivos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};