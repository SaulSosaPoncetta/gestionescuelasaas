<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Telefono;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    /**
     * Obtiene los datos completos del usuario autenticado
     * con sus relaciones cargadas.
     */
    public function getPerfil(): Usuario
    {
        return Auth::user()->load(['rol', 'localidad', 'telefonos', 'dnis']);
    }

    /**
     * Actualiza los datos del usuario autenticado.
     * Los teléfonos se reemplazan si se envían.
     */
    public function updatePerfil(Usuario $usuario, array $data): Usuario
{
    $campos = array_filter([
        'nombre'           => $data['nombre']           ?? null,
        'apellido'         => $data['apellido']         ?? null,
        'nombre_usuario'   => $data['nombre_usuario']   ?? null,
        'email'            => $data['email']            ?? null,
        'genero'           => $data['genero']           ?? null,
        'id_localidad'     => $data['id_localidad']     ?? null,
        'id_pais'          => $data['id_pais']          ?? null,
        'domicilio'        => $data['domicilio']        ?? null,
        'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
        'estado'           => $data['estado']           ?? null,
    ], fn($v) => !is_null($v));

    if (!empty($data['password'])) {
        $campos['password_hash'] = Hash::make($data['password']);
    }

    $usuario->update($campos);

    // ← AGREGÁ esto para actualizar el DNI
    if (!empty($data['dni'])) {
        \App\Models\Dni::where('id_usuario', $usuario->id_usuario)->delete();
        \App\Models\Dni::create([
            'id_usuario'  => $usuario->id_usuario,
            'dni'         => $data['dni'],
            'cuil'        => $data['cuil'] ?? null,
            'id_tipo_dni' => $data['id_tipo_dni'] ?? null,
        ]);
    }

    if (!empty($data['telefonos'])) {
        Telefono::where('id_usuario', $usuario->id_usuario)->delete();

        Telefono::create([
            'id_usuario' => $usuario->id_usuario,
            'telefono'   => $data['telefonos'],
        ]);

    }

    return $usuario->load(['rol', 'localidad', 'telefonos', 'dnis']);
}
}