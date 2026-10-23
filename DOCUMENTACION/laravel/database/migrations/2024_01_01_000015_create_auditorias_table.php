<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->increments('id_auditoria');
            $table->unsignedInteger('id_categoria_auditoria');
            $table->unsignedInteger('id_usuario');
            $table->string('nombre_tabla');
            $table->integer('id_registro');
            $table->string('accion'); // 'insert', 'update', 'delete'
            $table->string('descripcion');
            $table->dateTime('fecha_hora');
            $table->timestamps();

            $table->foreign('id_categoria_auditoria')->references('id_categoria_auditoria')->on('categorias_auditoria');
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
