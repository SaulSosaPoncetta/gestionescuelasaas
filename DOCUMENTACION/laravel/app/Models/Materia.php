<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Materia extends Model
{
    use HasFactory;

    protected $table = 'materias';

    protected $primaryKey = 'id_materia';

    // public $timestamps = false;

    protected $fillable = [
        'id_tipo_materia',
        'nota_promocionable',
        'nota_aprobada',
        'nombre',
        'anio',
        'orden_anio',
        'carga_horaria_anual',
        'tfo_edi_udi',
        'acreditable_final',
        'promocionable',
        'optativa',
        'id_usuario_administrador',
        'id_carrera',
        'id_plan',
        'id_ciclo', // nuevo
        ];

        // nueva relación
    
    protected $casts = [
        'tfo_edi_udi'        => 'boolean',
        'acreditable_final'  => 'boolean',
        'promocionable'      => 'boolean',
        'optativa'           => 'boolean',
    ];

    // Relaciones
    public function tipoMateria()
    {
        return $this->belongsTo(TipoMateria::class, 'id_tipo_materia', 'id_tipo_materia');
    }

    public function cicloLectivo()
    {
        return $this->belongsTo(CicloLectivo::class, 'id_ciclo', 'id_ciclo');
    }

    public function planEstudio()
    {
        return $this->belongsTo(PlanEstudio::class, 'id_plan', 'id_plan');
    }

    public function materiaDocentes()
    {
        return $this->hasMany(MateriaDocente::class, 'id_materia', 'id_materia');
    }

    public function inscripciones()
    {
        return $this->hasMany(InscripcionMateria::class, 'id_materia', 'id_materia');
    }

    public function administrador()
    {
        return $this->belongsTo(Administrador::class, 'id_usuario_administrador', 'id_usuario');
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function correlativas()
    {
        return $this->hasMany(Correlativa::class, 'id_materia', 'id_materia');
    }

    public function correlativasDe()
    {
        return $this->hasMany(Correlativa::class, 'id_materia_correlativa', 'id_materia');
    }

    public function docentes()
    {
        return $this->belongsToMany(Docente::class, 'materia_docente', 'id_materia', 'id_usuario_docente')
                    ->withPivot('id_usuario', 'situacion_revista');
    }

  public function notas()
    {
        return $this->hasMany(NotaCuatrimestre::class, 'id_materia', 'id_materia'); 
    }

    public function notasFinales()
    {
        return $this->hasMany(NotaFinal::class, 'id_materia', 'id_materia');
    }

    public function mesasExamen()
    {
        return $this->hasMany(MesaExamen::class, 'id_materia', 'id_materia');
    }
    public function archivos()
    {  
        return $this->belongsToMany(TipoArchivo::class, 'archivo_materia', 'id_materia', 'id_tipo_archivo');
    }
   

    protected function codigo(): Attribute
    {
        return Attribute::make(
            get: fn () => str_pad($this->id_materia, 4, '0', STR_PAD_LEFT),
        );
    }

    protected function acreditacion(): Attribute
    {
        return Attribute::make(
            get: fn () => !$this->acreditable_final
                ? 'Promoción directa'
                : ($this->promocionable ? 'Regular, Promoción' : 'Regular'),
        );
    }

    protected function anioCurricularTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => self::nombreAnio($this->anio),
        );
    }

    /**
     * Etiqueta legible para un año curricular (1 => "Primer Año", etc).
     */
    public static function nombreAnio(?int $anio): string
    {
        $nombres = [
            1 => 'Primer Año',
            2 => 'Segundo Año',
            3 => 'Tercer Año',
            4 => 'Cuarto Año',
            5 => 'Quinto Año',
        ];

        return $nombres[$anio] ?? '—';
    }

    protected function correlativasTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->correlativas
                ->map(fn ($c) => $c->materiaCorrelativa?->nombre)
                ->filter()
                ->unique()
                ->implode(', '),
        );
    }

}
