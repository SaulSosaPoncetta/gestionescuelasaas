<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id_usuario');
            $table->string('nombre');
            $table->string('apellido');
            $table->string('nombre_usuario');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->unsignedInteger('id_rol');
            $table->string('genero')->nullable();
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->unsignedInteger('id_localidad')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('domicilio')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('id_rol')->references('id_rol')->on('roles');
            $table->foreign('id_localidad')->references('id_localidad')->on('localidades');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
