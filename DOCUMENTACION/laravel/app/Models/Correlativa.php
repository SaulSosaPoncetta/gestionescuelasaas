<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Correlativa extends Model
{
    use HasFactory;

    protected $table = 'correlativas';

    public $incrementing = false;
    // public $timestamps = false;

    protected $primaryKey = null; 
    protected $fillable = [
        'id_materia',
        'id_materia_correlativa',
        'correlativa_cursada',
        'correlativa_final',
        'descripcion',
    ];

    protected $casts = [
        'correlativa_cursada' => 'boolean',
        'correlativa_final'   => 'boolean',
    ];

    // Relaciones
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function materiaCorrelativa()
    {
        return $this->belongsTo(Materia::class, 'id_materia_correlativa', 'id_materia');
    }
}