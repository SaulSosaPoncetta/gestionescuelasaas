<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ayuda_rol', function (Blueprint $table) {
            $table->unsignedInteger('id_ayuda');
            $table->unsignedInteger('id_rol');

            $table->primary(['id_ayuda', 'id_rol']);

            $table->foreign('id_ayuda')
                ->references('id_ayuda')
                ->on('ayudas')
                ->onDelete('cascade');

            $table->foreign('id_rol')
                ->references('id_rol')
                ->on('roles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ayuda_rol');
    }
};