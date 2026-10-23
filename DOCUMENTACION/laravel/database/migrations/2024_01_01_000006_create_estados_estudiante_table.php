<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_estudiante', function (Blueprint $table) {
            $table->increments('id_estado_estudiante');
            $table->string('descripcion_estado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_estudiante');
    }
};
