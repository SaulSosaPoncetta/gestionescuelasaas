<?php

namespace App\Listeners;

use App\Services\AuditoriaService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class AuditoriaListener
{
    protected AuditoriaService $auditoriaService;

    public function __construct(AuditoriaService $auditoriaService)
    {
        $this->auditoriaService = $auditoriaService;
    }

    /**
     * Registra un evento de login en la tabla auditorias.
     */
    public function handleLogin(Login $event): void
    {
        $this->auditoriaService->registrar(
            actor: $event->user,
            categoria: 'Login',
            accion: 'login',
            descripcion: 'Inicio de sesión desde IP ' . Request::ip()
        );
    }

    /**
     * Registra un evento de logout en la tabla auditorias.
     */
    public function handleLogout(Logout $event): void
    {
        // En logout, $event->user puede venir null si la sesión ya se invalidó.
        if (!$event->user) {
            return;
        }

        $this->auditoriaService->registrar(
            actor: $event->user,
            categoria: 'Logout',
            accion: 'logout',
            descripcion: 'Cierre de sesión desde IP ' . Request::ip()
        );
    }
}
