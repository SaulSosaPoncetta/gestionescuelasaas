<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CicloLectivo extends Model
{
    use HasFactory;

    protected $table = 'ciclos_lectivos'; 

    protected $primaryKey = 'id_ciclo';

    protected $fillable = [
        'anio',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'inicio_matriculacion',
        'fin_matriculacion',
        'estado',
        'id_usuario_administracion', 
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'inicio_matriculacion' => 'date',
        'fin_matriculacion' => 'date',
    ];

    // Relaciones
    public function administrador()
    {
        return $this->belongsTo(Administrador::class, 'id_usuario_administracion', 'id_usuario');
    }

    public function mesasExamen()
    {
        return $this->hasMany(MesaExamen::class, 'id_ciclo', 'id_ciclo');
    }

    public function materiaDocentes()
    {
        return $this->hasMany(MateriaDocente::class, 'id_ciclo', 'id_ciclo');
    }

    // Helpers de estado
    public function estaAbierto(): bool
    {
        return $this->estado === 'abierto';
    }

    public function estaCerrado(): bool
    {
        return $this->estado === 'cerrado';
    }

    public function abrir(): void
    {
        $this->update(['estado' => 'abierto']);
    }

    public function cerrar(): void
    {
        $this->update(['estado' => 'cerrado']);
    }

    // Scopes
    public function scopeAbiertos($query)
    {
        return $query->where('estado', 'abierto');
    }

    public function scopeCerrados($query)
    {
        return $query->where('estado', 'cerrado');
    }

    public function matriculacionAbierta(): bool
    {
        $hoy = now()->toDateString();

        return $hoy >= $this->inicio_matriculacion->toDateString()
            && $hoy <= $this->fin_matriculacion->toDateString();
    }
    public function carreras()
{
    return $this->belongsToMany(
        Carrera::class,
        'carrera_ciclo_lectivo',
        'id_ciclo',
        'id_carrera'
    )->withTimestamps();
}
}