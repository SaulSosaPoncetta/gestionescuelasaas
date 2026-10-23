<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha en la que el sistema detectó que el alumno acreditó el 100% de
     * las materias de la carrera (analítico completo) y lo pasó a Egresado.
     * Es la fecha que se muestra como "Fecha recibido" en el listado de
     * Egresados.
     */
    public function up(): void
    {
        Schema::table('estudiante_carrera', function (Blueprint $table) {
            $table->date('fecha_egreso')->nullable()->after('id_estado_carrera');
        });
    }

    public function down(): void
    {
        Schema::table('estudiante_carrera', function (Blueprint $table) {
            $table->dropColumn('fecha_egreso');
        });
    }
};
