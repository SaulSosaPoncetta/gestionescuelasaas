<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InscripcionMateria extends Model
{
    use HasFactory;

    protected $table = 'inscripciones_materia'; // era 'inscripciones', incorrecto

    public $incrementing = false;
    // public $timestamps = false;

    protected $primaryKey = null; 

    protected $fillable = [
        'id_usuario',           
        'id_materia',        
        'id_ciclo',             
        'fecha_inscripcion',
        'id_tipo_estudiante',   
        'id_estado_estudiante', 
        'contador_finales',     
        'fecha_vencimiento',    
        'libro',                
        'folio',                
        'division',             
    ];

    protected $casts = [
        'fecha_inscripcion' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    // Relaciones
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_usuario', 'id_usuario');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function cicloLectivo()
    {
        return $this->belongsTo(CicloLectivo::class, 'id_ciclo', 'id_ciclo');
    }

    public function tipoEstudiante()
    {
        return $this->belongsTo(TipoEstudiante::class, 'id_tipo_estudiante', 'id_tipo_estudiante');
    }

    public function estadoEstudiante()
    {
        return $this->belongsTo(EstadoEstudiante::class, 'id_estado_estudiante', 'id_estado_estudiante');
    }
}