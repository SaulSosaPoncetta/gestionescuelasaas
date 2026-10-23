<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanEstudio extends Model
{
    use HasFactory;

    protected $table = 'planes_estudio'; 

    protected $primaryKey = 'id_plan';

    // public $timestamps = false;

    protected $fillable = [
        'titulo', // faltaba
        'descripcion',
    ];

    // Relaciones
    public function materias()
    {
        return $this->hasMany(Materia::class, 'id_plan', 'id_plan');
    }

    public function resoluciones()
    {
        return $this->belongsToMany(ResolucionCarrera::class, 'plan_resolucion', 'id_plan', 'id_resolucion')
                    ->withPivot('id_carrera', 'id_tipo_archivo', 'total_horas', 'fecha');
    }
}