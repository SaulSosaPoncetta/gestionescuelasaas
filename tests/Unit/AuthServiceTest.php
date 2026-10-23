<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AuthService;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthServiceTest extends TestCase
{
    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();
    }

    //  CASO 1: Password::sendResetLink devuelve RESET_LINK_SENT
    public function test_send_reset_link_exitoso(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->with(['email' => 'test@example.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $resultado = $this->service->sendResetLink(['email' => 'test@example.com']);

        $this->assertEquals(__('passwords.sent'), $resultado);
    }

    //  CASO 2: El usuario no existe → lanza ValidationException
    public function test_lanza_excepcion_si_usuario_no_existe(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::INVALID_USER);

        $this->expectException(ValidationException::class);

        $this->service->sendResetLink(['email' => 'noexiste@example.com']);
    }

    //  CASO 3: Throttled → lanza ValidationException
    public function test_lanza_excepcion_si_throttled(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_THROTTLED);

        $this->expectException(ValidationException::class);

        $this->service->sendResetLink(['email' => 'test@example.com']);
    }

    //  CASO 4: Verifica que el mensaje de error sea el correcto
    public function test_mensaje_de_error_es_correcto(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::INVALID_USER);

        try {
            $this->service->sendResetLink(['email' => 'test@example.com']);
            $this->fail('Se esperaba una ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
            $this->assertEquals(__('passwords.user'), $e->errors()['email'][0]);
        }
    }
}