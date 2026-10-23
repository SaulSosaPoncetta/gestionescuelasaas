<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CategoriaAuditoria extends Model
{
    use HasFactory;
    protected $table = 'categorias_auditoria';

    protected $primaryKey = 'id_categoria_auditoria';

    // public $timestamps = false;
    protected $fillable = [
        'descripcion_categoria',
    ];
}