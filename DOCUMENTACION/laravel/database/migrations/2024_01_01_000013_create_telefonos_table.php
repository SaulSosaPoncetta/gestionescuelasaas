<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telefonos', function (Blueprint $table) {
            $table->string('telefono');
            $table->unsignedInteger('id_usuario');

            $table->primary(['telefono', 'id_usuario']);
            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telefonos');
    }
};
