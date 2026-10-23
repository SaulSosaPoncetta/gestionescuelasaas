<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\ResetPasswordNotification;

// use Illuminate\Database\Eloquent\SoftDeletes; 

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable, CanResetPassword;
    // SoftDeletes;

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'nombre',
        'apellido',
        'nombre_usuario',
        'email',
        'password_hash',
        'id_rol',
        'genero',
        'estado',
        'id_localidad',
        'id_pais',
        'fecha_nacimiento',
        'domicilio',
    ];

    protected $hidden = [
        'password_hash',
    ];
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // -------------------------------------------------------------------------
    // Relaciones existentes
    // -------------------------------------------------------------------------
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'id_usuario', 'id_usuario');
    }

    public function docente()
    {
        return $this->hasOne(Docente::class, 'id_usuario', 'id_usuario');
    }

    public function localidad()
    {
        return $this->belongsTo(Localidad::class, 'id_localidad', 'id_localidad');
    }

    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'id_usuario', 'id_usuario');
    }

    // -------------------------------------------------------------------------
    // Relaciones nuevas (necesarias para alumnosSec y alumnoPerfil)
    // -------------------------------------------------------------------------

    public function dnis()
    {
        return $this->hasMany(Dni::class, 'id_usuario', 'id_usuario');
    }

    public function telefonos()
    {
        return $this->hasMany(Telefono::class, 'id_usuario', 'id_usuario');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'id_pais', 'id_pais');
    }
}