<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoMateria extends Model
{
    use HasFactory;

    protected $table = 'tipos_materia';

    protected $primaryKey = 'id_tipo_materia';

    // public $timestamps = false;

    protected $fillable = [
        'descripcion_tipo_materia',
    ];
}