<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AuthService;
use App\Models\Usuario;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;

class LoginTest extends TestCase
{
    /**
     * Prueba 1: Éxito en validación y redirección
     */
    public function test_login_valida_y_redirecciona_correctamente()
    {
        // =========================================================================
        // 1. ARRANGE
        // =========================================================================
        // El controller ahora instancia AuthService con 'new' en vez de inyección,
        // por eso usamos instance() para forzar el mock en el contenedor de Laravel
        // antes de que el controller lo construya.
        $mockAuthService = \Mockery::mock(AuthService::class);
        $mockAuthService->shouldReceive('login')
            ->once()
            ->withAnyArgs()
            ->andReturn(true);

        $this->app->instance(AuthService::class, $mockAuthService);

        // =========================================================================
        // 2. ACT
        // =========================================================================
        // El campo ahora se llama 'login' (no 'identificador') — alineado al controller nuevo
        $response = $this->post('/login', [
            'login'    => 'admin@mail.com',
            'password' => '12345678',
        ]);

        // =========================================================================
        // 3. ASSERT
        // =========================================================================
        $response->assertRedirect('/home');
    }

    /**
     * Prueba 2: Errores de validación por campos ausentes
     */
    public function test_login_falla_si_faltan_campos()
    {
        // =========================================================================
        // 2. ACT
        // =========================================================================
        $response = $this->post('/login', []);

        // =========================================================================
        // 3. ASSERT
        // =========================================================================
        // El campo se llama 'login' ahora, no 'identificador'
        $response->assertSessionHasErrors(['login', 'password']);
    }

    /**
     * Prueba 3: Credenciales incorrectas muestran error en el formulario
     */
    public function test_login_falla_con_credenciales_incorrectas()
    {
        // =========================================================================
        // 1. ARRANGE
        // =========================================================================
        $mockAuthService = \Mockery::mock(AuthService::class);
        $mockAuthService->shouldReceive('login')
            ->once()
            ->withAnyArgs()
            ->andThrow(ValidationException::withMessages([
                'login' => 'Usuario, DNI o contraseña incorrectos.',
            ]));

        $this->app->instance(AuthService::class, $mockAuthService);

        // =========================================================================
        // 2. ACT
        // =========================================================================
        $response = $this->post('/login', [
            'login'    => 'noexiste@mail.com',
            'password' => 'passwordmal',
        ]);

        // =========================================================================
        // 3. ASSERT
        // =========================================================================
        $response->assertSessionHasErrors(['login']);
        $response->assertRedirect(); // vuelve al formulario
    }

    /**
     * Prueba 4: Usuario deshabilitado no puede ingresar
     */
    public function test_login_falla_si_usuario_deshabilitado()
    {
        // =========================================================================
        // 1. ARRANGE
        // =========================================================================
        $mockAuthService = \Mockery::mock(AuthService::class);
        $mockAuthService->shouldReceive('login')
            ->once()
            ->withAnyArgs()
            ->andThrow(ValidationException::withMessages([
                'login' => 'Tu cuenta se encuentra deshabilitada. Contactá al administrador.',
            ]));

        $this->app->instance(AuthService::class, $mockAuthService);

        // =========================================================================
        // 2. ACT
        // =========================================================================
        $response = $this->post('/login', [
            'login'    => 'deshabilitado@mail.com',
            'password' => '12345678',
        ]);

        // =========================================================================
        // 3. ASSERT
        // =========================================================================
        $response->assertSessionHasErrors(['login']);
    }

    /**
     * Prueba 5: Logout invalida la sesión y redirige al login
     */
    public function test_logout_redirige_al_login()
    {
        // =========================================================================
        // 1. ARRANGE
        // =========================================================================
        $mockAuthService = \Mockery::mock(AuthService::class);
        $mockAuthService->shouldReceive('logout')
            ->once()
            ->withNoArgs();

        $this->app->instance(AuthService::class, $mockAuthService);

        // =========================================================================
        // 2. ACT
        // =========================================================================
        $response = $this->post('/logout');

        // =========================================================================
        // 3. ASSERT
        // =========================================================================
        $response->assertRedirect('/login');
    }
}