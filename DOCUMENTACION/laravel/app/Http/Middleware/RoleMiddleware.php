<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Mapea el rol (tal como viene en $usuario->rol->descripcion, en minúsculas)
     * a la route name de su home propio.
     */
    private const HOME_POR_ROL = [
        'administrador' => 'home',    // -> /homeSec
        'estudiante'    => 'homeEst', // -> /homeEst
        'docente'    => 'homeDoc', // -> /homeDoc
    ];

    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $usuario = auth()->user();

        if (!$usuario->rol) {
            abort(403, 'El usuario no tiene un rol asignado.');
        }

        $rolUsuario = strtolower(trim($usuario->rol->descripcion));

        $rolesPermitidos = array_map(
            fn($rol) => strtolower(trim($rol)),
            $roles
        );

        if (!in_array($rolUsuario, $rolesPermitidos)) {
            // En vez de abort(403), lo mandamos a SU propio home
            $homeRoute = self::HOME_POR_ROL[$rolUsuario] ?? 'login';

            return redirect()->route($homeRoute);
        }

        return $next($request);
    }
}
