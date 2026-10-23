<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclos_lectivos', function (Blueprint $table) {
            $table->increments('id_ciclo');
            $table->integer('anio');
            $table->string('descripcion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('inicio_matriculacion');
            $table->date('fin_matriculacion');
            $table->enum('estado', ['abierto', 'cerrado'])->default('abierto');
            $table->unsignedInteger('id_usuario_administracion')->nullable();
            $table->timestamps();

            $table->foreign('id_usuario_administracion')->references('id_usuario')->on('administradores');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos_lectivos');
    }
};
