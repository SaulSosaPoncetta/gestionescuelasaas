<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Administrador extends Model
{
    use HasFactory;
    // SoftDeletes;

    protected $table = 'administradores';

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
}
