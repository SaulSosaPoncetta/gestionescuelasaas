<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoArchivo extends Model
{
    use HasFactory;

    protected $table = 'tipos_archivo';

    protected $primaryKey = 'id_tipo_archivo';

    // public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'archivo',
    ];
    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'archivo_materia', 'id_tipo_archivo', 'id_materia');
    }
}