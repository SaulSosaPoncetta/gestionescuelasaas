<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas_examen', function (Blueprint $table) {
            $table->increments('id_mesa');
            $table->dateTime('fecha'); // fecha y hora del examen
            $table->unsignedInteger('id_tipo_mesa');
            $table->boolean('habilitado')->default(true);
            $table->unsignedInteger('id_materia');
            $table->string('aula')->nullable();
            $table->unsignedInteger('id_usuario_administracion')->nullable();
            $table->unsignedInteger('id_ciclo');
            $table->unsignedInteger('id_llamado')->nullable();
            $table->timestamps();

            $table->foreign('id_tipo_mesa')->references('id_tipo_mesa')->on('tipos_mesa_examen');
            $table->foreign('id_materia')->references('id_materia')->on('materias');
            $table->foreign('id_usuario_administracion')->references('id_usuario')->on('administradores');
            $table->foreign('id_ciclo')->references('id_ciclo')->on('ciclos_lectivos');
            $table->foreign('id_llamado')->references('id_llamado')->on('llamados')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesas_examen');
    }
};
