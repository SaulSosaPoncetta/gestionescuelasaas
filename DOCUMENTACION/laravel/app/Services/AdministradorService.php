<?php

namespace App\Services;

use App\Models\Administrador;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Dni;
use App\Models\Telefono;
use App\Models\Localidad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdministradorService
{
    public function buscar(array $filtros): LengthAwarePaginator
    {
        $query = Administrador::with(['usuario.dnis', 'usuario.telefonos'])
            ->select('administradores.id_usuario')
            ->join('usuarios', 'usuarios.id_usuario', '=', 'administradores.id_usuario')
            ->leftJoin('dnis', 'usuarios.id_usuario', '=', 'dnis.id_usuario')
            ->with(['usuario.rol', 'usuario.localidad', 'usuario.dnis', 'usuario.telefonos']);

        if (!empty($filtros['nombre_apellido'])) {
            $busqueda = '%' . $filtros['nombre_apellido'] . '%';
            $query->where(function ($q) use ($busqueda) {
                $q->where('usuarios.nombre', 'like', $busqueda)
                ->orWhere('usuarios.apellido', 'like', $busqueda)
                ->orWhereRaw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) LIKE ?", [$busqueda]);
            });
        }

        if (!empty($filtros['dni'])) {
            $query->where('dnis.dni', 'like', '%' . $filtros['dni'] . '%');
        }

        if (!empty($filtros['estado'])) {
            $query->where('usuarios.estado', $filtros['estado']);
        }

        $query->distinct();

        return $query->orderBy('usuarios.apellido')->paginate(15);
    }

    public function getAll(): Collection
    {
        return Administrador::with([
            'usuario.rol',
            'usuario.localidad',
            'usuario.dnis',
            'usuario.telefonos',
        ])->get();
    }

    public function getTiposDni(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\TipoDni::orderBy('descripcion')->get();
    }

    public function getPaises(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Pais::orderBy('nombre_pais')->get();
    }

    public function getById(int $id): Administrador
    {
        return Administrador::with([
            'usuario.rol',
            'usuario.localidad',
            'usuario.dnis',
            'usuario.telefonos',
        ])
        ->where('id_usuario', $id)
        ->firstOrFail();
    }

    public function getLocalidades(): \Illuminate\Database\Eloquent\Collection
    {
        return Localidad::orderBy('nombre_localidad')->get();
    }

    public function create(array $data): Administrador
    {
        return DB::transaction(function () use ($data) {

            $rol = Rol::where('descripcion', 'Administrador')->first();

            if (!$rol) {
                throw new \Exception('No se encontró el rol de Administrador. Verificá la tabla roles.');
            }

            $usuario = Usuario::create([
                'nombre'           => $data['nombre'],
                'apellido'         => $data['apellido'],
                'nombre_usuario'   => $data['nombre_usuario'],
                'email'            => $data['email'],
                'password_hash'    => Hash::make($data['password']),
                'id_rol'           => $rol->id_rol,
                'genero'           => $data['genero'],
                'id_localidad'     => $data['id_localidad'],
                'id_pais'          => $data['id_pais'] ?? null,
                'domicilio'        => $data['domicilio'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'estado'           => 'Activo',
            ]);

            $administrador = Administrador::create([
                'id_usuario' => $usuario->id_usuario,
            ]);

            Dni::create([
                'id_usuario'  => $usuario->id_usuario,
                'dni'         => $data['dni'],
                'cuil'        => $data['cuil'] ?? null,
                'id_tipo_dni' => $data['id_tipo_dni'],
            ]);

            if (!empty($data['telefono'])) {
                Telefono::create([
                    'id_usuario' => $usuario->id_usuario,
                    'telefono'   => $data['telefono'],
                ]);
            }

            return $administrador->load([
                'usuario.rol',
                'usuario.localidad',
                'usuario.dnis',
                'usuario.telefonos',
            ]);
        });
    }

    public function update(int $id, array $data): Administrador
    {
        return DB::transaction(function () use ($id, $data) {

            $administrador = Administrador::where('id_usuario', $id)->firstOrFail();
            $usuario = $administrador->usuario;

            $camposUsuario = array_filter([
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
                $camposUsuario['password_hash'] = Hash::make($data['password']);
            }

            if (!empty($camposUsuario)) {
                $usuario->update($camposUsuario);
            }

            if (!empty($data['dni'])) {
                Dni::where('id_usuario', $id)->delete();
                Dni::create([
                    'id_usuario'  => $id,
                    'dni'         => $data['dni'],
                    'cuil'        => $data['cuil'] ?? null,
                    'id_tipo_dni' => $data['id_tipo_dni'],
                ]);
            }

            // Solo se admite UN teléfono por usuario. Si llega vacío, se borra el que hubiera;
            // si llega con valor, se reemplaza el existente (no se acumulan filas).
            if (array_key_exists('telefono', $data)) {
                Telefono::where('id_usuario', $id)->delete();

                if (!empty($data['telefono'])) {
                    Telefono::create([
                        'id_usuario' => $id,
                        'telefono'   => $data['telefono'],
                    ]);
                }
            }

            return $administrador->load([
                'usuario.rol',
                'usuario.localidad',
                'usuario.dnis',
                'usuario.telefonos',
            ]);
        });
    }

    public function toggleEstado(int $id): Administrador
    {
        return DB::transaction(function () use ($id) {

            $administrador = Administrador::where('id_usuario', $id)->firstOrFail();
            $usuario = $administrador->usuario;

            $usuario->update([
                'estado' => $usuario->estado === 'Activo' ? 'Inactivo' : 'Activo',
            ]);

            return $administrador->load([
                'usuario.rol',
                'usuario.localidad',
                'usuario.dnis',
                'usuario.telefonos',
            ]);
        });
    }
}
