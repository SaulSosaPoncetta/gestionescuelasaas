<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoDni extends Model
{
    use HasFactory;

    protected $table = 'tipos_dni';

    protected $primaryKey = 'id_tipo_dni';

    protected $fillable = [
        'descripcion',
    ];

    public function dnis()
    {
        return $this->hasMany(Dni::class,'id_tipo_dni','id_tipo_dni');
    }
}
