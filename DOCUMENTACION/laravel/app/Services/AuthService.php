<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Dni;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;

class AuthService
{
    /**
     * Autenticar al usuario por email, nombre_usuario o DNI + password.
     * Lanza excepción si las credenciales son incorrectas o el usuario está deshabilitado.
     *
     * Nota: la verificación de reCAPTCHA se hace en el controller (LoginController),
     * no acá, porque es una preocupación de la capa HTTP/anti-bot y no de la
     * lógica de negocio de autenticación.
     */
    public function login(array $data, string $ip): bool
    {
        $loginValue = $data['login'];

        $usuario = $this->resolverUsuario($loginValue);

        if (!$usuario || !Hash::check($data['password'], $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'login' => 'Usuario, DNI o contraseña incorrectos.',
            ]);
        }

        // Verificar que el usuario esté habilitado (cuando exista la columna)
        if (isset($usuario->habilitado) && !$usuario->habilitado) {
            throw ValidationException::withMessages([
                'login' => 'Tu cuenta se encuentra deshabilitada. Contactá al administrador.',
            ]);
        }

        Auth::login($usuario);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        // El registro en auditorias queda a cargo de AuditoriaListener,
        // que escucha el evento Login disparado por Auth::login() más
        // arriba. Antes acá también se insertaba a mano y quedaban dos
        // filas por cada inicio de sesión.

        return true;
    }

    /**
     * Cerrar la sesión del usuario autenticado.
     */
    public function logout(): void
    {
        Auth::logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }

    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            $data,
            function (Usuario $usuario, string $password) {
                $usuario->forceFill([
                    'password_hash' => Hash::make($password),
                ])->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    /**
     * Enviar el link de reseteo de contraseña al email indicado.
     */
    public function sendResetLink(array $data): string
    {
        $status = Password::sendResetLink($data);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return __($status);
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    /**
     * Resuelve el usuario a partir del valor ingresado:
     *   - Si es email      → busca por email en usuarios
     *   - Si es numérico   → busca por DNI en la tabla dnis
     *   - Cualquier otro   → busca por nombre_usuario en usuarios
     */
    private function resolverUsuario(string $loginValue): ?Usuario
    {
        if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
            return Usuario::where('email', $loginValue)->first();
        }

        if (ctype_digit($loginValue)) {
            $dni = Dni::where('dni', $loginValue)->first();
            return $dni?->usuario;
        }

        return Usuario::where('nombre_usuario', $loginValue)->first();
    }
}