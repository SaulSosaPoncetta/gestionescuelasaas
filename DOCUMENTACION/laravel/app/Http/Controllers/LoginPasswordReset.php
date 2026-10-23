<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginPasswordReset extends Controller
{
    protected AuthService $authService;

    public function __construct()
    {
        $this->authService = app(AuthService::class);
    }

    // GET /forgot-password
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    // POST /forgot-password
    public function sendPasswordResetToken(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:usuarios,email',
        ]);

        try {
            $mensaje = $this->authService->sendResetLink($data);

            return back()->with('status', $mensaje);

        } catch (ValidationException $e) {
            return back()
                ->withInput()
                ->withErrors($e->errors());
        }
    }

    // GET /reset-password/{token}
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    // POST /reset-password
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        try {
            $mensaje = $this->authService->resetPassword($data);

            return redirect()->route('login')->with('status', $mensaje);

        } catch (ValidationException $e) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors($e->errors());
        }
    }
}