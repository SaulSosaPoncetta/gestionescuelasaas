<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Administrador;
use App\Models\Usuario;
use App\Models\Dni;
use App\Models\Telefono;
use App\Services\AdministradorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdministradorControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function crearRolAdministracion(): int
    {
        return DB::table('roles')->insertGetId([
            'descripcion' => 'Administracion',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function crearTiposDni(): int
    {
        return DB::table('tipos_dni')->insertGetId([
            'descripcion' => 'DNI',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function crearAdministrador(array $overrides = []): Administrador
    {
        $idRol = $this->crearRolAdministracion();

        $usuario = Usuario::create(array_merge([
            'nombre'         => 'Juan',
            'apellido'       => 'Pérez',
            'nombre_usuario' => 'jperez' . rand(1000, 9999),
            'email'          => 'admin' . rand(1000, 9999) . '@mail.com',
            'password_hash'  => Hash::make('12345678'),
            'id_rol'         => $idRol,
            'genero'         => 'M',
            'id_localidad'   => null,
        ], $overrides));

        $administrador = Administrador::create(['id_usuario' => $usuario->id_usuario]);

        $idTipoDni = $this->crearTiposDni();

        Dni::create([
            'id_usuario'  => $usuario->id_usuario,
            'dni'         => '4055566' . rand(0, 9),
            'id_tipo_dni' => $idTipoDni,
        ]);

        Telefono::create([
            'id_usuario' => $usuario->id_usuario,
            'telefono'   => '1122334455',
        ]);

        return $administrador->load(['usuario.rol', 'usuario.dnis', 'usuario.telefonos']);
    }

    private function mockService(callable $configurar): void
    {
        $mock = \Mockery::mock(AdministradorService::class);
        $configurar($mock);
        $this->app->instance(AdministradorService::class, $mock);
    }

    private function crearLocalidad(): int
    {
        $idPais = DB::table('paises')->insertGetId([
            'nombre_pais' => 'Argentina',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return DB::table('localidades')->insertGetId([
            'id_pais'          => $idPais,
            'codigo_postal'    => 7400,
            'nombre_localidad' => 'Olavarría',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // index()
    // -------------------------------------------------------------------------

    public function test_index_devuelve_vista_con_administradores(): void
    {
        $this->markTestSkipped('Pendiente: la vista Secretaria.administracionSec no existe todavía.');
    }

    // -------------------------------------------------------------------------
    // show()
    // -------------------------------------------------------------------------

    public function test_show_devuelve_vista_con_perfil(): void
    {
        $this->markTestSkipped('Pendiente: la vista Secretaria.administracionPerfil no existe todavía.');
    }

    // -------------------------------------------------------------------------
    // store()
    // -------------------------------------------------------------------------

   public function test_store_crea_administrador_con_datos_validos(): void
{
    $idLocalidad   = $this->crearLocalidad();
    $idTipoDni     = $this->crearTiposDni();
    $administrador = $this->crearAdministrador();

    $this->mockService(function ($mock) use ($administrador) {
        $mock->shouldReceive('create')
            ->once()
            ->withAnyArgs()
            ->andReturn($administrador);
    });

    $response = $this->postJson('/administradorAgregar', [
        'nombre'         => 'María',
        'apellido'       => 'García',
        'nombre_usuario' => 'mgarcia',
        'email'          => 'maria@mail.com',
        'password'       => '12345678',
        'genero'         => 'F',
        'id_localidad'   => $idLocalidad,
        'dni'            => '30555666',
        'id_tipo_dni'    => $idTipoDni,
        'telefonos'      => ['1122334455'],
    ]);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);
}

public function test_store_devuelve_datos_del_administrador_creado(): void
{
    $idLocalidad   = $this->crearLocalidad();
    $idTipoDni     = $this->crearTiposDni();
    $administrador = $this->crearAdministrador();

    $this->mockService(function ($mock) use ($administrador) {
        $mock->shouldReceive('create')
            ->once()
            ->withAnyArgs()
            ->andReturn($administrador);
    });

    $response = $this->postJson('/administradorAgregar', [
        'nombre'         => 'María',
        'apellido'       => 'García',
        'nombre_usuario' => 'mgarcia',
        'email'          => 'maria@mail.com',
        'password'       => '12345678',
        'genero'         => 'F',
        'id_localidad'   => $idLocalidad,
        'dni'            => '30555666',
        'id_tipo_dni'    => $idTipoDni,
        'telefonos'      => ['1122334455'],
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['success', 'message', 'data']);
}

    public function test_store_falla_con_body_vacio(): void
    {
       $response = $this->postJson('/administradorAgregar', []);

        $response->assertStatus(422);
        //$response->assertJson(['success' => false]);
        $response->assertJsonStructure(['errors']);
    }

    public function test_store_falla_si_falta_nombre(): void
    {
       $response = $this->postJson('/administradorAgregar', [
            'apellido'       => 'García',
            'nombre_usuario' => 'mgarcia',
            'email'          => 'maria@mail.com',
            'password'       => '12345678',
            'genero'         => 'F',
            'id_localidad'   => null,
            'dni'            => '30555666',
            'tipo_dni'       => 'DNI',
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nombre']);
    }

    public function test_store_falla_con_email_invalido(): void
    {
        $response = $this->postJson('/administradorAgregar', [
            'nombre'         => 'María',
            'apellido'       => 'García',
            'nombre_usuario' => 'mgarcia',
            'email'          => 'esto-no-es-email',
            'password'       => '12345678',
            'genero'         => 'F',
            'id_localidad'   => null,
            'dni'            => '30555666',
            'tipo_dni'       => 'DNI',
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_store_falla_con_password_menor_a_8_caracteres(): void
    {
        $response = $this->postJson('/administradorAgregar', [
            'nombre'         => 'María',
            'apellido'       => 'García',
            'nombre_usuario' => 'mgarcia',
            'email'          => 'maria@mail.com',
            'password'       => '123',
            'genero'         => 'F',
            'id_localidad'   => null,
            'dni'            => '30555666',
            'tipo_dni'       => 'DNI',
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_store_falla_si_telefonos_esta_vacio(): void
    {
       $response = $this->postJson('/administradorAgregar', [
            'nombre'         => 'María',
            'apellido'       => 'García',
            'nombre_usuario' => 'mgarcia',
            'email'          => 'maria@mail.com',
            'password'       => '12345678',
            'genero'         => 'F',
            'id_localidad'   => null,
            'dni'            => '30555666',
            'tipo_dni'       => 'DNI',
            'telefonos'      => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['telefonos']);
    }

    public function test_store_falla_si_telefonos_no_es_array(): void
    {
        $response = $this->postJson('/administradorAgregar', [
            'nombre'         => 'María',
            'apellido'       => 'García',
            'nombre_usuario' => 'mgarcia',
            'email'          => 'maria@mail.com',
            'password'       => '12345678',
            'genero'         => 'F',
            'id_localidad'   => null,
            'dni'            => '30555666',
            'tipo_dni'       => 'DNI',
            'telefonos'      => '1122334455',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['telefonos']);
    }

    public function test_store_falla_con_genero_invalido(): void
    {
        $response = $this->postJson('/administradorAgregar', [
            'nombre'         => 'María',
            'apellido'       => 'García',
            'nombre_usuario' => 'mgarcia',
            'email'          => 'maria@mail.com',
            'password'       => '12345678',
            'genero'         => 'Femenino',
            'id_localidad'   => null,
            'dni'            => '30555666',
            'tipo_dni'       => 'DNI',
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['genero']);
    }

    public function test_store_falla_si_localidad_no_existe(): void
    {
        $response = $this->postJson('/administradorAgregar', [
            'nombre'         => 'María',
            'apellido'       => 'García',
            'nombre_usuario' => 'mgarcia',
            'email'          => 'maria@mail.com',
            'password'       => '12345678',
            'genero'         => 'F',
            'id_localidad'   => 99999,
            'dni'            => '30555666',
            'tipo_dni'       => 'DNI',
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['id_localidad']);
    }

    public function test_store_falla_con_email_duplicado(): void
    {
        $this->crearAdministrador(['email' => 'duplicado@mail.com']);
        $idLocalidad = $this->crearLocalidad();

        $response = $this->postJson('/administradorAgregar', [
            'nombre'         => 'Otro',
            'apellido'       => 'Usuario',
            'nombre_usuario' => 'otro_user',
            'email'          => 'duplicado@mail.com',
            'password'       => '12345678',
            'genero'         => 'M',
            'id_localidad'   => $idLocalidad,
            'dni'            => '99999999',
            'tipo_dni'       => 'DNI',
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_store_falla_con_nombre_usuario_duplicado(): void
    {
        $this->crearAdministrador(['nombre_usuario' => 'jperez_unico']);
        $idLocalidad = $this->crearLocalidad();

        $response = $this->postJson('/administradorAgregar', [
            'nombre'         => 'Otro',
            'apellido'       => 'Usuario',
            'nombre_usuario' => 'jperez_unico',
            'email'          => 'nuevo@mail.com',
            'password'       => '12345678',
            'genero'         => 'M',
            'id_localidad'   => $idLocalidad,
            'dni'            => '99999998',
            'tipo_dni'       => 'DNI',
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nombre_usuario']);
    }

    public function test_store_falla_con_dni_duplicado(): void
    {
        $administrador = $this->crearAdministrador();
        $dniExistente  = $administrador->usuario->dnis->first()->dni;
        $idLocalidad   = $this->crearLocalidad();

        $response = $this->postJson('/administradorAgregar', [
            'nombre'         => 'Otro',
            'apellido'       => 'Usuario',
            'nombre_usuario' => 'nuevo_user',
            'email'          => 'nuevo@mail.com',
            'password'       => '12345678',
            'genero'         => 'M',
            'id_localidad'   => $idLocalidad,
            'dni'            => $dniExistente,
            'tipo_dni'       => 'DNI',
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dni']);
    }

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

   public function test_update_modifica_administrador_con_datos_validos(): void
{
    $idLocalidad   = $this->crearLocalidad();
    $administrador = $this->crearAdministrador();
    // Recuperar el id_tipo_dni del DNI que ya existe en la BD
    $idTipoDni     = $administrador->usuario->dnis->first()->id_tipo_dni;

    $this->mockService(function ($mock) use ($administrador) {
        $mock->shouldReceive('update')
            ->once()
            ->withAnyArgs()
            ->andReturn($administrador);
    });

    $response =$this->putJson("/administrador/{$administrador->id_usuario}", [
        'nombre'         => 'Carlos',
        'apellido'       => 'Rodríguez',
        'email'          => 'carlos@mail.com',
        'nombre_usuario' => 'crodriguez',
        'genero'         => 'M',
        'id_localidad'   => $idLocalidad,
        'dni'            => '30555667',
        'id_tipo_dni'    => $idTipoDni,
        'telefonos'      => ['1122334455'],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
}

    public function test_update_devuelve_mensaje_y_datos(): void
    {
        $idLocalidad   = $this->crearLocalidad();
        $administrador = $this->crearAdministrador();
        $idTipoDni = $administrador->usuario->dnis->first()->id_tipo_dni;

        $this->mockService(function ($mock) use ($administrador) {
            $mock->shouldReceive('update')
                ->once()
                ->withAnyArgs()
                ->andReturn($administrador);
        });

        $response=$this->putJson("/administrador/{$administrador->id_usuario}", [
            'nombre'         => 'Carlos',
            'apellido'       => 'Rodríguez',
            'email'          => 'carlos@mail.com',
            'nombre_usuario' => 'crodriguez',
            'genero'         => 'M',
            'id_localidad'   => $idLocalidad,
            'dni'            => '30555667',
            'id_tipo_dni'    => $idTipoDni,
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_update_falla_con_body_vacio(): void
    {
        $administrador = $this->crearAdministrador();

        $response=$this->putJson("/administrador/{$administrador->id_usuario}", []);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $response->assertJsonStructure(['errors']);
    }

    public function test_update_falla_con_email_invalido(): void
    {
        $administrador = $this->crearAdministrador();
        $idTipoDni = $administrador->usuario->dnis->first()->id_tipo_dni;

        $response = $this->putJson("/administrador/{$administrador->id_usuario}", [
            'nombre'         => 'Carlos',
            'apellido'       => 'Rodríguez',
            'email'          => 'no-es-email',
            'nombre_usuario' => 'crodriguez',
            'genero'         => 'M',
            'id_localidad'   => null,
            'dni'            => '30555667',
            'id_tipo_dni'    => $idTipoDni,
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_update_falla_con_genero_invalido(): void
    {
        $administrador = $this->crearAdministrador();
        $idTipoDni = $administrador->usuario->dnis->first()->id_tipo_dni;

        $response = $this->putJson("/administrador/{$administrador->id_usuario}", [
            'nombre'         => 'Carlos',
            'apellido'       => 'Rodríguez',
            'email'          => 'carlos@mail.com',
            'nombre_usuario' => 'crodriguez',
            'genero'         => 'Masculino',
            'id_localidad'   => null,
            'dni'            => '30555667',
            'id_tipo_dni'    => $idTipoDni,
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['genero']);
    }

    public function test_update_falla_si_telefonos_esta_vacio(): void
    {
        $administrador = $this->crearAdministrador();
        $idTipoDni = $administrador->usuario->dnis->first()->id_tipo_dni;

        $response = $this->putJson("/administrador/{$administrador->id_usuario}", [
            'nombre'         => 'Carlos',
            'apellido'       => 'Rodríguez',
            'email'          => 'carlos@mail.com',
            'nombre_usuario' => 'crodriguez',
            'genero'         => 'M',
            'id_localidad'   => null,
            'dni'            => '30555667',
            'id_tipo_dni'    => $idTipoDni,
            'telefonos'      => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['telefonos']);
    }

    public function test_update_permite_mismo_email_del_propio_usuario(): void
    {
        $idLocalidad   = $this->crearLocalidad();
        $administrador = $this->crearAdministrador(['email' => 'propio@mail.com']);
        $idTipoDni = $administrador->usuario->dnis->first()->id_tipo_dni;

        $this->mockService(function ($mock) use ($administrador) {
            $mock->shouldReceive('update')
                ->once()
                ->withAnyArgs()
                ->andReturn($administrador);
        });

        $response = $this->putJson("/administrador/{$administrador->id_usuario}", [
            'nombre'         => 'Juan',
            'apellido'       => 'Pérez',
            'email'          => 'propio@mail.com',
            'nombre_usuario' => $administrador->usuario->nombre_usuario,
            'genero'         => 'M',
            'id_localidad'   => $idLocalidad,
            'dni'            => $administrador->usuario->dnis->first()->dni,
            'id_tipo_dni'    => $idTipoDni,
            'telefonos'      => ['1122334455'],
        ]);

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // toggleEstado()
    // -------------------------------------------------------------------------

    public function test_toggle_estado_cambia_estado_del_administrador(): void
{
    $administrador = $this->crearAdministrador();

    $response = $this->patchJson("/administradores/{$administrador->id_usuario}/estado");

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    $response->assertJsonStructure(['success', 'message', 'estado', 'data']);
}
}
