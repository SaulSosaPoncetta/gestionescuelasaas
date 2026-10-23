<?php

namespace App\Http\Controllers;

use App\Services\AuditoriaService;
use App\Models\Auditoria;

class AuditoriaController extends Controller
{
    protected AuditoriaService $auditoriaService;

    public function __construct(AuditoriaService $auditoriaService)
    {
        $this->auditoriaService = $auditoriaService;
    }

    // -------------------------------------------------------------------------
    // GET /auditoria
    // Lista de movimientos registrados en el sistema (login/logout y altas,
    // modificaciones y habilitaciones sobre administradores, docentes y
    // alumnos). El filtro por nombre/apellido/DNI se resuelve del lado del
    // cliente con DataTables, igual que en el resto de las pantallas.
    // -------------------------------------------------------------------------
    public function index()
    {
        $auditorias = $this->auditoriaService->listar();

        return view('Secretaria.auditoria', compact('auditorias'));
    }

    // -------------------------------------------------------------------------
    // GET /auditoria/{id}/comprobante
    // Comprobante imprimible de un movimiento puntual de la auditoría,
    // reusando la plantilla genérica de comprobante.blade.php.
    // -------------------------------------------------------------------------
    public function comprobante(int $id)
    {
        $auditoria = Auditoria::with('usuario')->findOrFail($id);

        $comprobante = (object) [
            'numero'    => str_pad($auditoria->id_auditoria, 8, '0', STR_PAD_LEFT),
            'usuario'   => $auditoria->usuario?->nombre_usuario ?? 'Usuario eliminado',
            'fecha'     => $auditoria->fecha_hora?->format('d/m/Y'),
            'hora'      => $auditoria->fecha_hora?->format('H:i'),
            'operacion' => ucfirst($auditoria->descripcion),
        ];

        return view('comprobante', compact('comprobante'));
    }
}
