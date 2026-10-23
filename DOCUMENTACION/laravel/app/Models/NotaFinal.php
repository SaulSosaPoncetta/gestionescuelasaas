<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotaFinal extends Model
{
    use HasFactory;

    protected $table = 'notas_final';

    protected $primaryKey = 'id_nota';

    // public $timestamps = false;

    protected $fillable = [
        'id_mesa',
        'valor',
        'baja',
        'id_tipo_archivo',
        'fecha_carga',
        'id_usuario',
        'id_materia',
    ];

    protected $casts = [
        'valor'       => 'decimal:2',
        'baja'        => 'boolean',
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

    public function mesaExamen()
    {
        return $this->belongsTo(MesaExamen::class, 'id_mesa', 'id_mesa');
    }

    public function tipoArchivo()
    {
        return $this->belongsTo(TipoArchivo::class, 'id_tipo_archivo', 'id_tipo_archivo');
    }
}