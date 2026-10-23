<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dnis', function (Blueprint $table) {
            $table->string('dni');
            $table->string('cuil', 13)->nullable();
            $table->unsignedInteger('id_usuario');
            $table->unsignedBigInteger('id_tipo_dni');

            $table->primary(['dni', 'id_usuario']);
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $table->foreign('id_tipo_dni')->references('id_tipo_dni')->on('tipos_dni');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dnis');
    }
};
