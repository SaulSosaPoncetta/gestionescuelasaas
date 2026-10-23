<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivo_examen', function (Blueprint $table) {
            $table->unsignedInteger('id_tipo_archivo');
            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_mesa')->nullable();

            $table->primary(['id_tipo_archivo', 'id_usuario']);
            $table->foreign('id_tipo_archivo')->references('id_tipo_archivo')->on('tipos_archivo');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $table->foreign('id_mesa')->references('id_mesa')->on('mesas_examen');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivo_examen');
    }
};
