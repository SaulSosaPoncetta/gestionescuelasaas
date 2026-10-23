<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResolucionCarrera extends Model
{
    use HasFactory;

    protected $table = 'resoluciones_carrera'; 

    protected $primaryKey = 'id_resolucion';

    // public $timestamps = false;

    protected $fillable = [
        'descripcion',
    ];

    // Relaciones
    public function planes()
    {
        return $this->belongsToMany(PlanEstudio::class, 'plan_resolucion', 'id_resolucion', 'id_plan');
    }
}
