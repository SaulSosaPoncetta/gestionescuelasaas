<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesa_examen_docente', function (Blueprint $table) {
            $table->unsignedInteger('id_mesa');
            $table->unsignedInteger('id_docente');
            $table->string('cargo', 20)->nullable();

            $table->primary(['id_mesa', 'id_docente']);
            $table->foreign('id_mesa')->references('id_mesa')->on('mesas_examen');
            $table->foreign('id_docente')->references('id_usuario')->on('docentes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesa_examen_docente');
    }
};
