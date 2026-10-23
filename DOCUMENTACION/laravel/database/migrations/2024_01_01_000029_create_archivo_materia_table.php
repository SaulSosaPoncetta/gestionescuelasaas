<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivo_materia', function (Blueprint $table) {
            $table->unsignedInteger('id_tipo_archivo');
            $table->unsignedInteger('id_materia');

            $table->primary(['id_tipo_archivo', 'id_materia']);
            $table->foreign('id_tipo_archivo')->references('id_tipo_archivo')->on('tipos_archivo');
            $table->foreign('id_materia')->references('id_materia')->on('materias');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivo_materia');
    }
};
