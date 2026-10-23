<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Telefono extends Model
{
    use HasFactory;

    protected $table = 'telefonos';

    public $incrementing = false;
    // public $timestamps = false;

    protected $primaryKey = null; // Clave primaria compuesta

    protected $fillable = [
        'telefono',
        'id_usuario',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}