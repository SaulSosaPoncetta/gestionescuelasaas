<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MesaExamenDocente extends Model
{
    use HasFactory;
    protected $table = 'mesa_examen_docente';
    public $incrementing = false;
    // public $timestamps = false;
    protected $primaryKey = null;
    protected $fillable = [
        'id_mesa',
        'id_docente',
    ];
}