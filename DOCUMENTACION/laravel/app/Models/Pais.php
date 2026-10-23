<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Pais extends Model
{
    use HasFactory; 
    protected $table = 'paises';

    protected $primaryKey = 'id_pais';

    // public $timestamps = false;
    protected $fillable = [
        'nombre_pais',
    ];
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_pais', 'id_pais');
    }
}