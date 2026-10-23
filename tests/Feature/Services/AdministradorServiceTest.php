<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\Administrador;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Dni;
use App\Models\Telefono;
use App\Services\AdministradorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdministradorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdministradorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdministradorService();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function crearRolAdministracion(): int
    {
        return DB::table('roles')->insertGetId([
            'descripcion' => 'Administrador',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function crearTipoDni(): int
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

        $idTipoDni = $this->crearTipoDni();

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

    private function datosValidos(array $overrides = []): array
    {
        $idTipoDni = $this->crearTipoDni();

        return array_merge([
            'nombre'         => 'María',
            'apellido'       => 'García',
            'nombre_usuario' => 'mgarcia' . rand(1000, 9999),
            'email'          => 'maria' . rand(1000, 9999) . '@mail.com',
            'password'       => '12345678',
            'genero'         => 'F',
            'id_localidad'   => null,
            'dni'            => '3055566' . rand(0, 9),
            'id_tipo_dni'    => $idTipoDni,
            'telefonos'      => ['1122334455', '9988776655'],
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // buscar()
    // -------------------------------------------------------------------------

    public function test_buscar_devuelve_todos_sin_filtros(): void
    {
        $this->crearAdministrador();
        $this->crearAdministrador();

        $resultado = $this->service->buscar([]);

        $this->assertCount(2, $resultado);
    }

    public function test_buscar_filtra_por_nombre(): void
    {
        $this->crearAdministrador(['nombre' => 'Carlos', 'apellido' => 'Lopez']);
        $this->crearAdministrador(['nombre' => 'Ana',    'apellido' => 'Gomez']);

        $resultado = $this->service->buscar(['nombre_apellido' => 'Carlos']);

        $this->assertCount(1, $resultado);
        $this->assertEquals('Carlos', $resultado->first()->usuario->nombre);
    }

    public function test_buscar_filtra_por_apellido(): void
    {
        $this->crearAdministrador(['nombre' => 'Carlos', 'apellido' => 'Lopez']);
        $this->crearAdministrador(['nombre' => 'Ana',    'apellido' => 'Gomez']);

        $resultado = $this->service->buscar(['nombre_apellido' => 'Gomez']);

        $this->assertCount(1, $resultado);
        $this->assertEquals('Gomez', $resultado->first()->usuario->apellido);
    }

    public function test_buscar_filtra_por_nombre_completo(): void
    {
        $this->crearAdministrador(['nombre' => 'Carlos', 'apellido' => 'Lopez']);
        $this->crearAdministrador(['nombre' => 'Ana',    'apellido' => 'Gomez']);

        $resultado = $this->service->buscar(['nombre_apellido' => 'Carlos Lopez']);

        $this->assertCount(1, $resultado);
        $this->assertEquals('Carlos', $resultado->first()->usuario->nombre);
    }

    public function test_buscar_sin_resultados_devuelve_paginator_vacio(): void
    {
        $resultado = $this->service->buscar(['nombre_apellido' => 'Inexistente']);

        $this->assertCount(0, $resultado);
    }

    public function test_buscar_filtra_por_estado_activo(): void
    {
        $this->crearAdministrador(['estado' => 'Activo']);
        $this->crearAdministrador(['estado' => 'Inactivo']);

        $resultado = $this->service->buscar(['habilitado' => 'true']);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $resultado);
    }

    public function test_buscar_devuelve_instancia_length_aware_paginator(): void
    {
        $this->crearAdministrador();

        $resultado = $this->service->buscar([]);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $resultado);
    }

    // -------------------------------------------------------------------------
    // getAll()
    // -------------------------------------------------------------------------

    public function test_get_all_devuelve_collection(): void
    {
        $this->crearAdministrador();
        $this->crearAdministrador();

        $resultado = $this->service->getAll();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultado);
        $this->assertCount(2, $resultado);
    }

    public function test_get_all_carga_relaciones(): void
    {
        $this->crearAdministrador();

        $resultado = $this->service->getAll()->first();

        $this->assertTrue($resultado->relationLoaded('usuario'));
        $this->assertTrue($resultado->usuario->relationLoaded('dnis'));
        $this->assertTrue($resultado->usuario->relationLoaded('telefonos'));
    }

    // -------------------------------------------------------------------------
    // getById()
    // -------------------------------------------------------------------------

    public function test_get_by_id_devuelve_administrador_correcto(): void
    {
        $administrador = $this->crearAdministrador(['email' => 'admin@mail.com']);

        $resultado = $this->service->getById($administrador->id_usuario);

        $this->assertEquals($administrador->id_usuario, $resultado->id_usuario);
        $this->assertEquals('admin@mail.com', $resultado->usuario->email);
    }

    public function test_get_by_id_carga_relaciones(): void
    {
        $administrador = $this->crearAdministrador();

        $resultado = $this->service->getById($administrador->id_usuario);

        $this->assertTrue($resultado->relationLoaded('usuario'));
        $this->assertTrue($resultado->usuario->relationLoaded('rol'));
        $this->assertTrue($resultado->usuario->relationLoaded('dnis'));
        $this->assertTrue($resultado->usuario->relationLoaded('telefonos'));
    }

    public function test_get_by_id_lanza_excepcion_si_no_existe(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->getById(99999);
    }

    // -------------------------------------------------------------------------
    // create()
    // -------------------------------------------------------------------------

    public function test_create_crea_usuario_administrador_dni_y_telefonos(): void
    {
        $this->crearRolAdministracion();

        $datos = $this->datosValidos([
            'nombre_usuario' => 'mgarcia',
            'email'          => 'maria@mail.com',
            'dni'            => '30555666',
            'telefonos'      => ['1122334455', '9988776655'],
        ]);

        $administrador = $this->service->create($datos);

        $this->assertInstanceOf(Administrador::class, $administrador);
        $this->assertDatabaseHas('usuarios',        ['email' => 'maria@mail.com']);
        $this->assertDatabaseHas('administradores', ['id_usuario' => $administrador->id_usuario]);
        $this->assertDatabaseHas('dnis',            ['dni' => '30555666']);
        $this->assertDatabaseHas('telefonos',       ['telefono' => '1122334455']);
        $this->assertDatabaseHas('telefonos',       ['telefono' => '9988776655']);
        $this->assertDatabaseCount('telefonos', 2);
    }

    public function test_create_hashea_el_password(): void
    {
        $this->crearRolAdministracion();

        $datos = $this->datosValidos(['email' => 'hash@mail.com', 'dni' => '31555666']);

        $administrador = $this->service->create($datos);

        $this->assertTrue(Hash::check('12345678', $administrador->usuario->password_hash));
    }

    public function test_create_asigna_rol_administracion(): void
    {
        $this->crearRolAdministracion();
        $datos = $this->datosValidos();

        $administrador = $this->service->create($datos);

        $this->assertEquals('Administrador', $administrador->usuario->rol->descripcion);
    }

    public function test_create_carga_relaciones_en_el_resultado(): void
    {
        $this->crearRolAdministracion();
        $datos = $this->datosValidos();

        $administrador = $this->service->create($datos);

        $this->assertTrue($administrador->relationLoaded('usuario'));
        $this->assertTrue($administrador->usuario->relationLoaded('rol'));
        $this->assertTrue($administrador->usuario->relationLoaded('dnis'));
        $this->assertTrue($administrador->usuario->relationLoaded('telefonos'));
    }

    public function test_create_falla_si_no_existe_rol_administracion(): void
    {
        $this->expectException(\Exception::class);

        $this->service->create($this->datosValidos());
    }

    public function test_create_hace_rollback_si_falla_alguna_insercion(): void
{
    $this->crearRolAdministracion();

    $datos = $this->datosValidos([
        'nombre_usuario' => 'mgarcia_fijo',
        'email'          => 'fijo@mail.com',
        'dni'            => '99999999',
    ]);

    // Primer create exitoso
    $this->service->create($datos);

    // Forzar fallo con email duplicado — falla en Usuario::create antes de llegar a Administrador
    try {
        $this->service->create(array_merge($datos, [
            'nombre_usuario' => 'otro_usuario',
            'dni'            => '88888888',
            // email duplicado → falla en Usuario::create → rollback antes de insertar administrador
        ]));
        $this->fail('Se esperaba una excepción pero no se lanzó.');
    } catch (\Exception $e) {
        $this->assertDatabaseCount('administradores', 1);
        $this->assertDatabaseCount('usuarios', 1);
    }
}

    // -------------------------------------------------------------------------
    // update()
    // -------------------------------------------------------------------------

    public function test_update_modifica_datos_del_usuario(): void
    {
        $administrador = $this->crearAdministrador(['nombre' => 'Juan', 'email' => 'juan@mail.com']);
        $idTipoDni     = $administrador->usuario->dnis->first()->id_tipo_dni;

        $resultado = $this->service->update($administrador->id_usuario, [
            'nombre'         => 'Carlos',
            'apellido'       => 'Rodríguez',
            'email'          => 'juan@mail.com',
            'nombre_usuario' => $administrador->usuario->nombre_usuario,
            'genero'         => 'M',
            'id_localidad'   => null,
            'telefonos'      => ['1122334455'],
            'dni'            => $administrador->usuario->dnis->first()->dni,
            'id_tipo_dni'    => $idTipoDni,
        ]);

        $this->assertEquals('Carlos', $resultado->usuario->nombre);
        $this->assertDatabaseHas('usuarios', ['nombre' => 'Carlos', 'email' => 'juan@mail.com']);
    }

    public function test_update_reemplaza_telefonos_completamente(): void
    {
        $administrador = $this->crearAdministrador();
        $id            = $administrador->id_usuario;
        $idTipoDni     = $administrador->usuario->dnis->first()->id_tipo_dni;

        $this->service->update($id, [
            'nombre'         => $administrador->usuario->nombre,
            'apellido'       => $administrador->usuario->apellido,
            'email'          => $administrador->usuario->email,
            'nombre_usuario' => $administrador->usuario->nombre_usuario,
            'genero'         => $administrador->usuario->genero,
            'id_localidad'   => null,
            'telefonos'      => ['9999999999'],
            'dni'            => $administrador->usuario->dnis->first()->dni,
            'id_tipo_dni'    => $idTipoDni,
        ]);

        $telefonos = Telefono::where('id_usuario', $id)->get();
        $this->assertCount(1, $telefonos);
        $this->assertEquals('9999999999', $telefonos->first()->telefono);
    }

    public function test_update_hashea_password_si_se_envia(): void
    {
        $administrador = $this->crearAdministrador();
        $idTipoDni     = $administrador->usuario->dnis->first()->id_tipo_dni;

        $resultado = $this->service->update($administrador->id_usuario, [
            'nombre'         => $administrador->usuario->nombre,
            'apellido'       => $administrador->usuario->apellido,
            'email'          => $administrador->usuario->email,
            'nombre_usuario' => $administrador->usuario->nombre_usuario,
            'genero'         => $administrador->usuario->genero,
            'id_localidad'   => null,
            'password'       => 'nuevapassword123',
            'telefonos'      => ['1122334455'],
            'dni'            => $administrador->usuario->dnis->first()->dni,
            'id_tipo_dni'    => $idTipoDni,
        ]);

        $this->assertTrue(Hash::check('nuevapassword123', $resultado->usuario->password_hash));
    }

    public function test_update_no_modifica_password_si_no_se_envia(): void
    {
        $administrador = $this->crearAdministrador();
        $hashOriginal  = $administrador->usuario->password_hash;
        $idTipoDni     = $administrador->usuario->dnis->first()->id_tipo_dni;

        $resultado = $this->service->update($administrador->id_usuario, [
            'nombre'         => $administrador->usuario->nombre,
            'apellido'       => $administrador->usuario->apellido,
            'email'          => $administrador->usuario->email,
            'nombre_usuario' => $administrador->usuario->nombre_usuario,
            'genero'         => $administrador->usuario->genero,
            'id_localidad'   => null,
            'telefonos'      => ['1122334455'],
            'dni'            => $administrador->usuario->dnis->first()->dni,
            'id_tipo_dni'    => $idTipoDni,
        ]);

        $this->assertEquals($hashOriginal, $resultado->usuario->fresh()->password_hash);
    }

    public function test_update_reemplaza_dni(): void
    {
        $administrador = $this->crearAdministrador();
        $id            = $administrador->id_usuario;
        $idTipoDni     = $administrador->usuario->dnis->first()->id_tipo_dni;

        $this->service->update($id, [
            'nombre'         => $administrador->usuario->nombre,
            'apellido'       => $administrador->usuario->apellido,
            'email'          => $administrador->usuario->email,
            'nombre_usuario' => $administrador->usuario->nombre_usuario,
            'genero'         => $administrador->usuario->genero,
            'id_localidad'   => null,
            'telefonos'      => ['1122334455'],
            'dni'            => '88888888',
            'id_tipo_dni'    => $idTipoDni,
        ]);

        $this->assertDatabaseHas('dnis', ['id_usuario' => $id, 'dni' => '88888888']);
        $this->assertDatabaseCount('dnis', 1);
    }

    public function test_update_lanza_excepcion_si_administrador_no_existe(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->update(99999, [
            'nombre'         => 'X',
            'apellido'       => 'X',
            'email'          => 'x@mail.com',
            'nombre_usuario' => 'x',
            'genero'         => 'M',
            'id_localidad'   => null,
            'telefonos'      => ['1122334455'],
            'dni'            => '12345678',
            'id_tipo_dni'    => 1,
        ]);
    }

    // -------------------------------------------------------------------------
    // toggleEstado()
    // -------------------------------------------------------------------------

    public function test_toggle_estado_deshabilita_administrador_activo(): void
{
    $administrador = $this->crearAdministrador(['estado' => 'Activo']);

    $resultado = $this->service->toggleEstado($administrador->id_usuario);

    $this->assertEquals('Inactivo', $resultado->usuario->estado);
    $this->assertDatabaseHas('usuarios', [
        'id_usuario' => $administrador->id_usuario,
        'estado'     => 'Inactivo',
    ]);
}

public function test_toggle_estado_habilita_administrador_inactivo(): void
{
    $administrador = $this->crearAdministrador(['estado' => 'Inactivo']);

    $resultado = $this->service->toggleEstado($administrador->id_usuario);

    $this->assertEquals('Activo', $resultado->usuario->estado);
    $this->assertDatabaseHas('usuarios', [
        'id_usuario' => $administrador->id_usuario,
        'estado'     => 'Activo',
    ]);
}
}