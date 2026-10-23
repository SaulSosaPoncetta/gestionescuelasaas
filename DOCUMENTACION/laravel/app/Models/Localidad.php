<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Localidad extends Model
{
    use HasFactory;

    protected $table = 'localidades';

    protected $primaryKey = 'id_localidad';

    // public $timestamps = false;

    protected $fillable = [
        'id_pais',
        'codigo_postal',
        'nombre_localidad',
    ];

    // Relaciones
    public function pais()
    {
        return $this->belongsTo(Pais::class, 'id_pais', 'id_pais');
    }
}