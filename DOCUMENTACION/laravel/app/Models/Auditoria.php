<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Auditoria extends Model
{
    use HasFactory;

    protected $table = 'auditorias';

    protected $primaryKey = 'id_auditoria';

    // public $timestamps = false;
    protected $fillable = [
        'id_categoria_auditoria',
        'id_usuario',
        'nombre_tabla',
        'id_registro',
        'accion',
        'descripcion',
        'fecha_hora',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    // Relaciones
    public function categoriaAuditoria()
    {
        return $this->belongsTo(CategoriaAuditoria::class, 'id_categoria_auditoria', 'id_categoria_auditoria');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}