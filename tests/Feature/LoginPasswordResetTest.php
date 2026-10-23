<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Rol;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

class LoginPasswordResetTest extends TestCase
{
    // RefreshDatabase es preferible a DatabaseMigrations — más rápido al usar transacciones
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Deshabilitamos CSRF para los tests de HTTP
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        // Creamos el rol base necesario para la FK de usuarios
        Rol::create(['descripcion' => 'Administracion']);
    }

    // -------------------------------------------------------------------------
    // Tests: envío del reset link
    // -------------------------------------------------------------------------

    public function test_envia_reset_link_con_email_valido(): void
    {
        // ARRANGE
        Usuario::factory()->create(['email' => 'test@example.com']);

        // El controller usa 'new AuthService()' así que registramos el mock
        // en el contenedor para que sea resuelto correctamente
        $this->app->instance(AuthService::class, \Mockery::mock(AuthService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendResetLink')
                ->once()
                ->withAnyArgs()
                ->andReturn(__('passwords.sent'));
        }));

        // ACT
        $response = $this->post('/forgot-password', ['email' => 'test@example.com']);

        // ASSERT
        $response->assertRedirect();
        $response->assertSessionHas('status', __('passwords.sent'));
    }

    public function test_falla_si_email_no_existe_en_db(): void
    {
        // ACT — no hay usuario con ese email en la BD
        $response = $this->post('/forgot-password', ['email' => 'noexiste@example.com']);

        // ASSERT — la validación exists:usuarios,email debe fallar
        $response->assertSessionHasErrors(['email']);
    }

    public function test_falla_si_email_tiene_formato_invalido(): void
    {
        // ACT
        $response = $this->post('/forgot-password', ['email' => 'esto-no-es-un-email']);

        // ASSERT
        $response->assertSessionHasErrors(['email']);
    }

    public function test_falla_si_email_esta_vacio(): void
    {
        // ACT
        $response = $this->post('/forgot-password', []);

        // ASSERT
        $response->assertSessionHasErrors(['email']);
    }

    public function test_falla_si_el_servicio_lanza_excepcion(): void
    {
        // ARRANGE
        Usuario::factory()->create(['email' => 'test@example.com']);

        $this->app->instance(AuthService::class, \Mockery::mock(AuthService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendResetLink')
                ->once()
                ->andThrow(\Illuminate\Validation\ValidationException::withMessages([
                    'email' => [__('passwords.throttled')],
                ]));
        }));

        // ACT
        $response = $this->post('/forgot-password', ['email' => 'test@example.com']);

        // ASSERT
        $response->assertSessionHasErrors(['email']);
    }
}