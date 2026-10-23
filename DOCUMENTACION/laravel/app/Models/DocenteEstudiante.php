<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocenteEstudiante extends Model
{
    use HasFactory;

    protected $table = 'docente_estudiante';

    public $incrementing = false;
    // public $timestamps = false;

    protected $primaryKey = null; 
    protected $fillable = [
        'id_usuario_docente',  
        'id_usuario_estudiante', 
        'fecha_hora',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    // Relaciones
    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_usuario_docente', 'id_usuario');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_usuario_estudiante', 'id_usuario');
    }
}