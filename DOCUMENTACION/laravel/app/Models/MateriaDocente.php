<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MateriaDocente extends Model
{
    use HasFactory;

    protected $table = 'materia_docente'; 

    public $incrementing = false;

    protected $primaryKey = null; 

    protected $fillable = [
        'id_materia',
        'id_usuario_docente',
        'id_usuario',
        'id_ciclo',
        'situacion_revista',
        'division',
        'fecha_inicio',
        'fecha_fin',
        'horas_semanales',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    // Relaciones
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class, 'id_usuario_docente', 'id_usuario');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function cicloLectivo()
    {
        return $this->belongsTo(CicloLectivo::class, 'id_ciclo', 'id_ciclo');
    }
}