<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\EstudianteCarrera;

class Carrera extends Model
{
    use HasFactory;

    protected $table = 'carreras';

    protected $primaryKey = 'id_carrera';

    // public $timestamps = false;

    protected $fillable = [
        'nombre',
        'titulo',
    ];

    public const NOMBRE_SIN_ASIGNAR = 'Sin asignar';

    public static function sinAsignar(): self
    {
        return static::firstOrCreate(
            ['nombre' => self::NOMBRE_SIN_ASIGNAR],
            ['titulo' => self::NOMBRE_SIN_ASIGNAR]
        );
    }
    // Relaciones
    public function resoluciones()
    {
        return $this->belongsToMany(ResolucionCarrera::class, 'plan_resolucion', 'id_carrera', 'id_resolucion')
                ->withPivot('id_plan', 'id_tipo_archivo', 'total_horas', 'fecha');
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_carrera', 'id_carrera', 'id_usuario')
                    ->using(EstudianteCarrera::class)
                    ->withPivot('cohorte', 'id_estado_carrera');
    }
    /**
     * Todos los registros de plan_resolucion vinculados a esta carrera
     * (una carrera podría tener más de uno con el tiempo: cambios de plan,
     * nuevas resoluciones, etc).
     */
    public function planResolucion()
    {
        return $this->hasMany(PlanResolucion::class, 'id_carrera', 'id_carrera');
    }
 
    /**
     * El registro de plan_resolucion "vigente" (el más reciente por fecha).
     * Acceso típico: $carrera->planVigente->plan->titulo
     */
    public function planVigente()
    {
        return $this->hasOne(PlanResolucion::class, 'id_carrera', 'id_carrera')
            ->orderByDesc('fecha');
    }

    public function materias()
    {
        return $this->hasMany(Materia::class, 'id_carrera', 'id_carrera');
    }

    public function ciclosLectivos()
{
    return $this->belongsToMany(
        CicloLectivo::class,
        'carrera_ciclo_lectivo',
        'id_carrera',
        'id_ciclo'
    )->withTimestamps();
}

}
