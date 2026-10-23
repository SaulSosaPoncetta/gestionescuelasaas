<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administracion_docente', function (Blueprint $table) {
            $table->unsignedInteger('id_usuario_administracion');
            $table->unsignedInteger('id_usuario_docente');
            $table->dateTime('fecha_hora');

            $table->primary(['id_usuario_administracion', 'id_usuario_docente', 'fecha_hora']);
            $table->foreign('id_usuario_administracion')->references('id_usuario')->on('administradores');
            $table->foreign('id_usuario_docente')->references('id_usuario')->on('docentes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administracion_docente');
    }
};
