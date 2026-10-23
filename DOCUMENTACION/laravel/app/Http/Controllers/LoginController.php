<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = app(AuthService::class);
    }

    // -------------------------------------------------------------------------
    // GET /login
    // -------------------------------------------------------------------------
    public function showLogin()
    {
        return view('auth.login', [
            'fallbackChallenge' => $this->generarFallbackChallenge(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /login
    // -------------------------------------------------------------------------
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'login'    => 'required|string',
                'password' => 'required|string',
            ], [
                'login.required'    => 'Ingresá tu usuario, DNI o mail.',
                'password.required' => 'Ingresá tu contraseña.',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'login' => $validator->errors()->first(),
                ]);
            }

            $data = $validator->validated();

            // Valida captcha de Google o el fallback offline, según corresponda
            $this->verificarCaptcha($request);

            $this->authService->login($data, $request->ip());

            switch (auth()->user()->rol->descripcion) {
                case 'Administrador':
                    return redirect()->route('home');

                case 'Estudiante':
                    return redirect()->route('homeEst');

                case 'Docente':
                    return redirect()->route('homeDoc');

                default:
                    return redirect('/login');
            }

        } catch (ValidationException $e) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors($e->errors());
        }
    }

    // -------------------------------------------------------------------------
    // POST /logout
    // -------------------------------------------------------------------------
    public function logout(Request $request)
    {
        $this->authService->logout();
        return redirect('/login');
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    private function generarFallbackChallenge(): array
    {
        $a = rand(1, 9);
        $b = rand(1, 9);
        session(['captcha_fallback_answer' => $a + $b]);

        return ['question' => "¿Cuánto es {$a} + {$b}?"];
    }

    private function verificarCaptcha(Request $request): void
    {
        $usaFallback = $request->boolean('captcha_offline');

        if ($usaFallback) {
            // No confiamos ciegamente en el flag del cliente: si el server
            // SÍ tiene salida a Google, no dejamos pasar por el fallback.
            try {
                $test = Http::timeout(2)->get('https://www.google.com/generate_204');
                if ($test->successful()) {
                    throw ValidationException::withMessages([
                        'login' => 'Por favor, completá el captcha normal.',
                    ]);
                }
            } catch (ConnectionException $e) {
                // Sin conexión confirmado desde el server también → dejamos pasar el fallback
            }

            $respuesta = (int) $request->input('captcha_fallback', -1);
            $esperada  = session('captcha_fallback_answer');
            session()->forget('captcha_fallback_answer');

            if ($esperada === null || $respuesta !== $esperada) {
                throw ValidationException::withMessages([
                    'login' => 'La respuesta de verificación no es correcta.',
                ]);
            }
            return;
        }

        if (!$request->filled('g-recaptcha-response')) {
            throw ValidationException::withMessages([
                'login' => 'Por favor, completá el captcha.',
            ]);
        }

        $response = Http::timeout(5)->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => config('services.recaptcha.secret_key'),
            'response' => $request->input('g-recaptcha-response'),
            'remoteip' => $request->ip(),
        ]);

        $success = $response->successful() && $response->json('success') === true;

        if (!$success) {
            throw ValidationException::withMessages([
                'login' => 'La verificación del captcha falló o expiró. Por favor, intentá de nuevo.',
            ]);
        }
    }
}