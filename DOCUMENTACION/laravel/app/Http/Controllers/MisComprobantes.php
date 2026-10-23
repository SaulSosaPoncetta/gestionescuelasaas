<?php
namespace App\Http\Controllers;

use App\Services\UsuarioService;
use Illuminate\View\View;

class UsuarioController extends Controller {
    protected $usuarioService;

    public function __construct(UsuarioService $usuarioService) {
        $this->usuarioService = $usuarioService;
    }

    public function historial(): View {
        $logs = $this->usuarioService->getHistorial();
        
        return view('usuarios.historial', compact('logs'));
    }
}
