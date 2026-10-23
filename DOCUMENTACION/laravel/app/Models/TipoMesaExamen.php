<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoMesaExamen extends Model
{
    use HasFactory;

    protected $table = 'tipos_mesa_examen';

    protected $primaryKey = 'id_tipo_mesa';

    // public $timestamps = false;

    protected $fillable = [
        'descripcion',
    ];
}