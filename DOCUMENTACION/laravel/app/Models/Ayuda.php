<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ayuda extends Model
{
    use HasFactory;

    protected $table = 'ayudas';

    protected $primaryKey = 'id_ayuda';

    protected $fillable = [
        'titulo_ayuda',
        'descripcion',
        'video_url',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'ayuda_rol',
            'id_ayuda',
            'id_rol',
            'id_ayuda',
            'id_rol'
        );
    }

}