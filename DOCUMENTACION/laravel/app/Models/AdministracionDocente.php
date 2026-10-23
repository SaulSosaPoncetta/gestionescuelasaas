<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdministracionDocente extends Model
{
    use HasFactory;

    protected $table = 'administracion_docente';

    public $incrementing = false;
    // public $timestamps = false;

    protected $primaryKey = null; 

    protected $fillable = [
        'id_usuario_administracion',
        'id_usuario_docente',
        'fecha_hora',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];
}
