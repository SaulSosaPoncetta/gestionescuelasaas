<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localidades', function (Blueprint $table) {
            $table->increments('id_localidad');
            $table->unsignedInteger('id_pais');
            $table->integer('codigo_postal');
            $table->string('nombre_localidad');
            $table->timestamps();

            $table->foreign('id_pais')->references('id_pais')->on('paises');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('localidades');
    }
};
