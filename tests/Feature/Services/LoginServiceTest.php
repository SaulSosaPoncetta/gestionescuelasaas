<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Dni;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class LoginServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Crea un usuario con DNI asociado.
     * Usa firstOrCreate en Rol para evitar duplicados entre tests.
     */
    private function crearUsuarioConDni(array $overrides = []): Usuario
    {
    // Insertamos el rol directo con DB:: para evitar cualquier problema del modelo
        $idRol = \Illuminate\Support\Facades\DB::table('roles')->insertGetId([
            'descripcion' => 'Administracion',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

    // Creamos el usuario sin factory para tener control total
    $usuario = Usuario::create(array_merge([
        'nombre'         => 'Test',
        'apellido'       => 'Usuario',
        'nombre_usuario' => 'testusuario' . rand(1000, 9999),
        'email'          => 'test' . rand(1000, 9999) . '@mail.com',
        'password_hash'  => Hash::make('12345678'),
        'id_rol'         => $idRol,
        'genero'         => 'Masculino',
        'id_localidad'   => null,
    ], $overrides));

    Dni::create([
        'id_usuario' => $usuario->id_usuario,
        'dni'        => '40555666',
        'tipo_dni'   => 'DNI',
    ]);

    return $usuario;
}

    // -------------------------------------------------------------------------
    // Tests: login por email
    // -------------------------------------------------------------------------

    public function test_autentica_usuario_por_email()
    {
        // ARRANGE
        $usuario = $this->crearUsuarioConDni(['email' => 'admin@mail.com']);

        // ACT
        $this->service->login([
            'login'    => 'admin@mail.com',
            'password' => '12345678',
        ], '127.0.0.1');

        // ASSERT
        $this->assertAuthenticatedAs($usuario);
    }

    // -------------------------------------------------------------------------
    // Tests: login por nombre_usuario
    // -------------------------------------------------------------------------

    public function test_autentica_usuario_por_nombre_usuario()
    {
        // ARRANGE
        $usuario = $this->crearUsuarioConDni(['nombre_usuario' => 'jperez']);

        // ACT
        $this->service->login([
            'login'    => 'jperez',
            'password' => '12345678',
        ], '127.0.0.1');

        // ASSERT
        $this->assertAuthenticatedAs($usuario);
    }

    // -------------------------------------------------------------------------
    // Tests: login por DNI
    // -------------------------------------------------------------------------

    public function test_autentica_usuario_por_dni()
    {
        // ARRANGE
        $usuario = $this->crearUsuarioConDni();

        // ACT
        $this->service->login([
            'login'    => '40555666',
            'password' => '12345678',
        ], '127.0.0.1');

        // ASSERT
        $this->assertAuthenticatedAs($usuario);
    }

    // -------------------------------------------------------------------------
    // Tests: credenciales incorrectas
    // -------------------------------------------------------------------------

    public function test_lanza_excepcion_con_password_incorrecto()
    {
        // ARRANGE
        $this->crearUsuarioConDni(['email' => 'admin@mail.com']);

        // ASSERT
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // ACT
        $this->service->login([
            'login'    => 'admin@mail.com',
            'password' => 'passwordincorrecto',
        ], '127.0.0.1');
    }

    public function test_lanza_excepcion_si_usuario_no_existe()
    {
        // ASSERT
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // ACT
        $this->service->login([
            'login'    => 'noexiste@mail.com',
            'password' => '12345678',
        ], '127.0.0.1');
    }

    // -------------------------------------------------------------------------
    // Tests: usuario deshabilitado
    // PENDIENTE: requiere columna 'habilitado' en tabla usuarios
    // Activar cuando el compañero agregue la migración
    // -------------------------------------------------------------------------

    public function test_lanza_excepcion_si_usuario_deshabilitado()
    {
        $this->markTestSkipped('Pendiente: requiere columna habilitado en usuarios.');
    }

    // -------------------------------------------------------------------------
    // Tests: auditoría
    // -------------------------------------------------------------------------

    public function test_registra_auditoria_al_autenticar()
    {
        // ARRANGE
        $usuario = $this->crearUsuarioConDni(['email' => 'admin@mail.com']);

        // ACT
        $this->service->login([
            'login'    => 'admin@mail.com',
            'password' => '12345678',
        ], '192.168.1.1');

        // ASSERT
        $this->assertDatabaseHas('auditorias', [
            'id_usuario'  => $usuario->id_usuario,
            'descripcion' => 'Inicio de sesión desde 192.168.1.1',
        ]);
    }
}