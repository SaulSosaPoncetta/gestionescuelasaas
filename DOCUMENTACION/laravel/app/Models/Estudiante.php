<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Estudiante extends Model
{
    use HasFactory; #SoftDeletes;

    protected $table = 'estudiantes';

    protected $primaryKey = 'id_usuario';

    public $incrementing = false;
    // public $timestamps = false;

    protected $fillable = [
        'id_usuario',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'estudiante_carrera', 'id_usuario', 'id_carrera')
                    ->withPivot('cohorte', 'id_estado_carrera');
    }

    /**
     * La carrera "actual" del alumno: la de cohorte más reciente. La
     * mayoría de los alumnos tiene una sola, pero el vínculo admite más
     * de una a lo largo del tiempo.
     */
    public function carreraActual()
    {
        return $this->carreras()->orderByDesc('estudiante_carrera.cohorte')->first();
    }

    public function tipo()
    {
        return $this->belongsTo(TipoEstudiante::class, 'id_tipo_estudiante', 'id_tipo_estudiante');
    }

    public function notas()
    {
        return $this->hasMany(NotaCuatrimestre::class, 'id_usuario', 'id_usuario'); // era NotaCursada
    }

    public function notasFinales()
    {
        return $this->hasMany(NotaFinal::class, 'id_usuario', 'id_usuario');
    }

    public function materias()
{
    return $this->belongsToMany(Materia::class, 'inscripciones_materia', 'id_usuario', 'id_materia'); // era 'inscripciones'
}
}
