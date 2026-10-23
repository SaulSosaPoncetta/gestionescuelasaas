<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanResolucion extends Model
{
    use HasFactory;

    protected $table = 'plan_resolucion';
    
    // $incrementing = true (default) ahora que hay id autoincremental

    protected $fillable = [
        'id_resolucion',
        'id_plan',
        'id_carrera',
        'id_tipo_archivo',
        'total_horas',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function plan()
    {
        return $this->belongsTo(PlanEstudio::class, 'id_plan', 'id_plan');
    }

    public function resolucion()
    {
        return $this->belongsTo(ResolucionCarrera::class, 'id_resolucion', 'id_resolucion');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function tipoArchivo()
    {
        return $this->belongsTo(TipoArchivo::class, 'id_tipo_archivo', 'id_tipo_archivo');
    }
}