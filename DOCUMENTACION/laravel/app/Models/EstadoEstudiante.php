<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EstadoEstudiante extends Model
{
    use HasFactory;

    protected $table = 'estados_estudiante';

    protected $primaryKey = 'id_estado_estudiante';

    // public $timestamps = false;

    protected $fillable = [
        'descripcion_estado',
    ];
}