<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MesaExamen extends Model
{
    use HasFactory;

    protected $table = 'mesas_examen'; 

    protected $primaryKey = 'id_mesa';

    // public $timestamps = false;

    protected $fillable = [
        'fecha',
        'id_tipo_mesa',
        'id_llamado',
        'habilitado',            
        'id_materia',          
        'aula',
        'id_usuario_administracion', 
        'id_ciclo',             
    ];

    protected $casts = [
        'fecha'      => 'datetime', 
        'habilitado' => 'boolean',
    ];

    // Relaciones
    public function tipoMesa()
    {
        return $this->belongsTo(TipoMesaExamen::class, 'id_tipo_mesa', 'id_tipo_mesa');
    }

    public function llamado()
    {
        return $this->belongsTo(Llamado::class, 'id_llamado', 'id_llamado');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'id_materia', 'id_materia');
    }

    public function administrador()
    {
        return $this->belongsTo(Administrador::class, 'id_usuario_administracion', 'id_usuario');
    }

    public function cicloLectivo()
    {
        return $this->belongsTo(CicloLectivo::class, 'id_ciclo', 'id_ciclo');
    }

    public function docentes()
    {
        return $this->belongsToMany(Docente::class, 'mesa_examen_docente', 'id_mesa', 'id_docente')
            ->withPivot('cargo'); // 'titular' | 'vocal' (no confundir con usuarios.id_rol)
    }

    // Los estudiantes de una mesa salen de notas_final: cada fila es un
    // estudiante inscripto a esa mesa (con o sin nota cargada todavía).
    public function notasFinal()
    {
        return $this->hasMany(NotaFinal::class, 'id_mesa', 'id_mesa');
    }
}