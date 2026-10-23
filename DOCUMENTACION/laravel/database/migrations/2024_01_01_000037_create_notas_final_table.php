<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_final', function (Blueprint $table) {
            $table->increments('id_nota');
            $table->unsignedInteger('id_mesa');
            $table->decimal('valor', 5, 2)->nullable();
            $table->unsignedInteger('id_tipo_archivo')->nullable();
            $table->dateTime('fecha_carga');
            $table->unsignedInteger('id_usuario'); // estudiante que rinde el final
            $table->unsignedInteger('id_materia');
            $table->boolean('baja')->default(false);
            $table->timestamps();

            $table->foreign('id_mesa')->references('id_mesa')->on('mesas_examen');
            $table->foreign('id_tipo_archivo')->references('id_tipo_archivo')->on('tipos_archivo');
            $table->foreign('id_usuario')->references('id_usuario')->on('estudiantes');
            $table->foreign('id_materia')->references('id_materia')->on('materias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_final');
    }
};
