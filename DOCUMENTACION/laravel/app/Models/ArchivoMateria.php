<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArchivoMateria extends Model
{
    use HasFactory;

    protected $table = 'archivo_materia';

    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'id_tipo_archivo',
        'id_materia',
    ];

    public function tipoArchivo()
    {
        return $this->belongsTo(TipoArchivo::class, 'id_tipo_archivo', 'id_tipo_archivo');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }
}