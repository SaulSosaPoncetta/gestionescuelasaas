<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArchivoExamen extends Model
{
    use HasFactory;

    protected $table = 'archivo_examen';

    public $incrementing = false;
    // public $timestamps = false;

    protected $primaryKey = null; 

    protected $fillable = [
        'id_tipo_archivo',
        'id_usuario',
        'id_mesa',
    ];

    // Relaciones
    public function tipoArchivo()
    {
        return $this->belongsTo(TipoArchivo::class, 'id_tipo_archivo', 'id_tipo_archivo');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function mesaExamen()
    {
        return $this->belongsTo(MesaExamen::class, 'id_mesa', 'id_mesa');
    }
}