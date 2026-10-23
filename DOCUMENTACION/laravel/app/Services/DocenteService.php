<?php

namespace App\Services;
use App\Models\Docente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DocenteService
{
    public function buscar(array $filtros): LengthAwarePaginator
    {
        $query = Docente::with(['usuario'])
        ->join('usuarios', 'docentes.id_usuario', '=', 'usuarios.id_usuario')
        ->leftJoin('dnis', 'usuarios.id_usuario', '=', 'dnis.id_usuario')
        ->select('docentes.*','usuarios.apellido');

        //Filtro por nombre y/o apellido
        if (!empty($filtros['nombre_apellido'])) {
            $busqueda = '%' . $filtros['nombre_apellido'] . '%';
            $query->where(function ($q) use ($busqueda) {
                $q->where('usuarios.nombre', 'like', $busqueda)
                ->orWhere('usuarios.apellido', 'like', $busqueda)
                ->orWhereRaw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) LIKE ?", [$busqueda]);
            });
        }

        //Filtro por dni
        if (!empty($filtros['dni'])) {
            $query->where('dnis.dni', 'like', '%' . $filtros['dni'] . '%');
        }

        // Filtro por estado
        if (!empty($filtros['estado'])) {
            $query->where('usuarios.estado', $filtros['estado']);
        }

        $query->distinct();
    return $query->orderBy('usuarios.apellido')->paginate(15);}

public function toggleEstado(int $id): Docente
    {
        return DB::transaction(function () use ($id) {

            $docente = Docente::where('id_usuario', $id)->firstOrFail();
            $usuario = $docente->usuario;

            $usuario->update([
                'estado' => $usuario->estado === 'Activo' ? 'Inactivo' : 'Activo',
            ]);

            return $docente->load([
                'usuario.rol',
                'usuario.localidad',
                'usuario.dnis',
                'usuario.telefonos',
            ]);
        });
    }
}
