<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llamados', function (Blueprint $table) {
            $table->increments('id_llamado');
            $table->string('descripcion', 100); // "Febrero/Marzo" | "Julio/Agosto" | "Noviembre/Diciembre"
            $table->json('meses_validos')->nullable(); // sin after(): el orden ya está dado por la posición en el Blueprint
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llamados');
    }
};