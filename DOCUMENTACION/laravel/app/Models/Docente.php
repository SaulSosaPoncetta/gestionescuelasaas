<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\SoftDeletes;

class Docente extends Model
{
    use HasFactory; // SoftDeletes;

    protected $table = 'docentes';

    protected $primaryKey = 'id_usuario';

    public $incrementing = false;
    // public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nro_legajo',
    ];

    // Relaciones
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
    public function mesasExamen()
    {
        return $this->belongsToMany(MesaExamen::class, 'mesa_examen_docente', 'id_docente', 'id_mesa');
    }

    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'materia_docente', 'id_usuario_docente', 'id_materia')
                    ->withPivot('situacion_revista', 'division')
                    ->with(['carrera', 'tipoMateria']);
    }
}
