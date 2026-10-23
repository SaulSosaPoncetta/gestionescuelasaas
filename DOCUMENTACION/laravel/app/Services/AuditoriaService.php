<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\CategoriaAuditoria;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;

class AuditoriaService
{
    // -------------------------------------------------------------------------
    // REGISTRAR
    // Punto único de escritura en la tabla auditorias. Cualquier controller o
    // listener que necesite dejar constancia de un movimiento pasa por acá,
    // así el criterio de creación queda en un solo lugar.
    // -------------------------------------------------------------------------

    /**
     * Registra un movimiento en la tabla de auditorías.
     *
     * @param Usuario|null $actor   Usuario logueado que ejecuta la acción (nunca el afectado).
     * @param string       $categoria    Ej: 'Login', 'Logout', 'Administradores', 'Docentes', 'Alumnos'.
     * @param string       $accion       Ej: 'login', 'logout', 'crear', 'actualizar', 'habilitar', 'deshabilitar'.
     * @param string       $descripcion  Texto legible de lo ocurrido.
     * @param string       $nombreTabla  Tabla afectada por la acción.
     * @param int|null     $idRegistro   Id del registro afectado (por defecto, el del propio actor).
     */
    public function registrar(
        ?Usuario $actor,
        string $categoria,
        string $accion,
        string $descripcion,
        string $nombreTabla = 'usuarios',
        ?int $idRegistro = null
    ): ?Auditoria {
        // Sin usuario autenticado no hay a quién imputarle el movimiento.
        if (!$actor) {
            return null;
        }

        $idCategoria = CategoriaAuditoria::firstOrCreate(
            ['descripcion_categoria' => $categoria]
        )->id_categoria_auditoria;

        return Auditoria::create([
            'id_categoria_auditoria' => $idCategoria,
            'id_usuario'             => $actor->id_usuario,
            'nombre_tabla'           => $nombreTabla,
            'id_registro'            => $idRegistro ?? $actor->id_usuario,
            'accion'                 => $accion,
            'descripcion'            => $descripcion,
            'fecha_hora'             => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // LISTAR
    // Para la pantalla de Auditoría: quién hizo qué y cuándo, con los datos
    // necesarios para armar el link "Ver perfil".
    // -------------------------------------------------------------------------

    public function listar(): Collection
    {
        return Auditoria::with([
                'usuario.dnis',
                'usuario.rol',
                'categoriaAuditoria',
            ])
            ->orderByDesc('fecha_hora')
            ->get();
    }

    // -------------------------------------------------------------------------
    // LISTAR PROPIOS
    // Para "Mis Comprobantes": la misma pantalla de Auditoría, pero solo con
    // los movimientos del usuario logueado (no los de todos).
    // -------------------------------------------------------------------------

    public function listarPropios(int $idUsuario): Collection
    {
        return Auditoria::with([
                'usuario.dnis',
                'usuario.rol',
                'categoriaAuditoria',
            ])
            ->where('id_usuario', $idUsuario)
            ->orderByDesc('fecha_hora')
            ->get();
    }
}
