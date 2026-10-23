<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotaCuatrimestre extends Model
{
    use HasFactory;

    protected $table = 'notas_cuatrimestre'; 

    protected $primaryKey = 'id_nota_cuatrimestre';

    // public $timestamps = false;

    protected $fillable = [
        'valor',        
        'fecha_carga',   
        'id_tipo_archivo',  
        'id_usuario',       
        'id_materia',       
        'id_ciclo',
        'periodo',
        'situacion',
    ];

    protected $casts = [
        'valor'       => 'decimal:2',
        'fecha_carga' => 'datetime', 
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

    public function tipoArchivo()
    {
        return $this->belongsTo(TipoArchivo::class, 'id_tipo_archivo', 'id_tipo_archivo');
    }

    public function cicloLectivo()
    {
        return $this->belongsTo(CicloLectivo::class, 'id_ciclo', 'id_ciclo');
    }
}