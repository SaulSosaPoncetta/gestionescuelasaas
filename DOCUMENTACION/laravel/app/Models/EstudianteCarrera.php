<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class EstudianteCarrera extends Model
{
    use HasFactory, AsPivot;

    protected $table = 'estudiante_carrera';

    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'id_usuario',
        'id_carrera',
        'cohorte',
        'id_estado_carrera',
        'fecha_egreso',
    ];

    protected $casts = [
        'cohorte'      => 'date',
        'fecha_egreso' => 'date',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Fuerza las claves del pivot aunque el modelo se instancie
        // "suelto" (no a través de la relación belongsToMany), para que
        // save()/delete() armen bien el WHERE.
        $this->setPivotKeys('id_usuario', 'id_carrera');
    }

    // Relaciones
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_usuario', 'id_usuario');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function estadoCarrera()
    {
        return $this->belongsTo(EstadoCarrera::class, 'id_estado_carrera', 'id_estado_carrera');
    }
}