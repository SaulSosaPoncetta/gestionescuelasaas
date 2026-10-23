<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoEstudiante extends Model
{
    use HasFactory;

    protected $table = 'tipos_estudiante';

    protected $primaryKey = 'id_tipo_estudiante';

    // public $timestamps = false;

    protected $fillable = [
        'descripcion_tipo',
    ];
}