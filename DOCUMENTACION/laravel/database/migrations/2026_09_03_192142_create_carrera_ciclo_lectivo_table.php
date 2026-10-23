<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrera_ciclo_lectivo', function (Blueprint $table) {
            $table->unsignedInteger('id_carrera');
            $table->unsignedInteger('id_ciclo');

            $table->foreign('id_carrera')
                ->references('id_carrera')->on('carreras')
                ->cascadeOnDelete();

            $table->foreign('id_ciclo')
                ->references('id_ciclo')->on('ciclos_lectivos')
                ->cascadeOnDelete();

            $table->primary(['id_carrera', 'id_ciclo']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrera_ciclo_lectivo');
    }
};