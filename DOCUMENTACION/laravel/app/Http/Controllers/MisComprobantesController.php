<?php

namespace App\Http\Controllers;

use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;

class MisComprobantesController extends Controller
{
    protected AuditoriaService $auditoriaService;

    public function __construct(AuditoriaService $auditoriaService)
    {
        $this->auditoriaService = $auditoriaService;
    }

    // -------------------------------------------------------------------------
    // GET /misComprobantes
    // Pantalla de Auditoría reciclada, mostrando solo los movimientos del
    // usuario logueado (no los de todos como en /auditoria).
    // -------------------------------------------------------------------------
    public function index()
    {
        $auditorias = $this->auditoriaService->listarPropios(Auth::id());

        return view('Secretaria.misComprobantes', compact('auditorias'));
    }
}
