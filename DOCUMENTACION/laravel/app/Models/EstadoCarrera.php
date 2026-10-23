<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EstadoCarrera extends Model
{
    use HasFactory;

    protected $table = 'estados_carrera';

    protected $primaryKey = 'id_estado_carrera';

    protected $fillable = [
        'estado',
    ];
}
