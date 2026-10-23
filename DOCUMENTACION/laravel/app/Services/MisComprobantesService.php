<?php
namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;

class UsuarioService {
    /**
     * Obtiene el historial de operaciones del usuario actual.
     */
    public function getHistorial() {
        // Obtenemos los logs a través de la relación definida en el modelo Usuario
        return Auth::user()->logs()->orderBy('fecha_hora', 'desc')->get();
    }
}
