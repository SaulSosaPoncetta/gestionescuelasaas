<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correlativas', function (Blueprint $table) {
            $table->unsignedInteger('id_materia');
            $table->unsignedInteger('id_materia_correlativa');
            $table->boolean('correlativa_cursada')->default(false);
            $table->boolean('correlativa_final')->default(false);
            $table->string('descripcion')->nullable();

            $table->primary(['id_materia', 'id_materia_correlativa']);
            $table->foreign('id_materia')->references('id_materia')->on('materias');
            $table->foreign('id_materia_correlativa')->references('id_materia')->on('materias');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correlativas');
    }
};
