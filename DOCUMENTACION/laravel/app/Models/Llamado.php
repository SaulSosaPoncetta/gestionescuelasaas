<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Llamado extends Model
{
    use HasFactory;

    protected $table = 'llamados';

    protected $primaryKey = 'id_llamado';

    protected $fillable = [
        'descripcion',
        'meses_validos',
    ];

    protected $casts = [
        'meses_validos' => 'array',
    ];
    // El año de un llamado concreto no vive acá: sale de
    // mesaExamen->cicloLectivo (cada mesa ya tiene su propio id_ciclo).
    public function mesasExamen()
    {
        return $this->hasMany(MesaExamen::class, 'id_llamado', 'id_llamado');
    }
}