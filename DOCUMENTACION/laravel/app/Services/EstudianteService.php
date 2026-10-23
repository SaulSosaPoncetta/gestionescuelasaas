<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Usuario;
use App\Models\Dni;
use App\Models\Telefono;
use App\Models\Localidad;
use App\Models\Pais;
use App\Models\TipoDni;
use App\Models\Rol;
use App\Models\Carrera;
use App\Models\EstudianteCarrera;
use App\Models\EstadoCarrera;
use App\Models\NotaCuatrimestre;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Collection;

class EstudianteService
{
    // -------------------------------------------------------------------------
    // BUSCAR
    // DataTable pagina en el cliente, devolvemos toda la colección de una vez.
    // -------------------------------------------------------------------------

    /**
     * Buscar estudiantes con filtros opcionales.
     * Filtros: nombre_apellido, dni, anio (cohorte), padron (id_usuario).
     */
    public function buscar(array $filtros): Collection
    {
        $query = Estudiante::with([
            'usuario.dnis',
            'usuario.telefonos',
            'usuario.localidad',
            'usuario.rol',
        ])
        ->join('usuarios', 'estudiantes.id_usuario', '=', 'usuarios.id_usuario')
        ->whereNull('usuarios.deleted_at')
        ->select('estudiantes.*');

        // Filtro por nombre o apellido
        if (!empty($filtros['nombre_apellido'])) {
            $busqueda = '%' . $filtros['nombre_apellido'] . '%';
            $query->where(function ($q) use ($busqueda) {
                $q->where('usuarios.nombre', 'like', $busqueda)
                  ->orWhere('usuarios.apellido', 'like', $busqueda)
                  ->orWhereRaw("(usuarios.nombre || ' ' || usuarios.apellido) like ?", [$busqueda]);
            });
        }

        // Filtro por DNI
        if (!empty($filtros['dni'])) {
            $query->join('dnis', 'estudiantes.id_usuario', '=', 'dnis.id_usuario')
                  ->where('dnis.dni', 'like', '%' . $filtros['dni'] . '%');
        }

        // Filtro por año (cohorte en estudiante_carrera)
        if (!empty($filtros['anio'])) {
            $query->join('estudiante_carrera', 'estudiantes.id_usuario', '=', 'estudiante_carrera.id_usuario')
                  ->whereYear('estudiante_carrera.cohorte', $filtros['anio']);
        }

        // Filtro por padrón (id_usuario)
        if (!empty($filtros['padron'])) {
            $query->where('estudiantes.id_usuario', $filtros['padron']);
        }

        $query->distinct();

        return $query->orderBy('usuarios.apellido')->get();
    }

    public function getCarreras(int $idUsuario): Collection
    {
        return EstudianteCarrera::with('carrera')
            ->where('id_usuario', $idUsuario)
            ->orderByDesc('cohorte')
            ->get();
    }

    // -------------------------------------------------------------------------
    // GET BY ID
    // -------------------------------------------------------------------------

    /**
     * Obtener un estudiante por su id_usuario con todas sus relaciones.
     */
    public function getById(int $idUsuario): Estudiante
    {
        return Estudiante::with([
            'usuario.localidad.pais',
            'usuario.rol',
            'usuario.telefonos',
            'usuario.dnis.tipoDni',
        ])
        ->where('id_usuario', $idUsuario)
        ->firstOrFail();
    }

    // -------------------------------------------------------------------------
    // HELPERS DE CATÁLOGOS
    // -------------------------------------------------------------------------

    public function getLocalidades(): \Illuminate\Database\Eloquent\Collection
    {
        return Localidad::with('pais')->orderBy('nombre_localidad')->get();
    }

    public function getTiposDni(): \Illuminate\Database\Eloquent\Collection
    {
        return TipoDni::orderBy('descripcion')->get();
    }

    public function getPaises(): \Illuminate\Database\Eloquent\Collection
    {
        return Pais::orderBy('nombre_pais')->get();
    }

    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------

    /**
     * Crear un nuevo estudiante.
     *
     * @param array $data {
     *   nombre, apellido, nombre_usuario, email, password,
     *   genero, id_localidad, id_pais (opcional), domicilio (opcional),
     *   fecha_nacimiento (opcional), dni, cuil (opcional), id_tipo_dni,
     *   telefono_celular (opcional),
     * }
     */
    public function create(array $data): Estudiante
    {
        return DB::transaction(function () use ($data) {

            $rol = Rol::where('descripcion', 'Estudiante')->first();

            // Si no hay un rol llamado exactamente "Estudiante", tomamos el id 2
            // (ajustar según la tabla roles real del proyecto)
            $idRol = $rol ? $rol->id_rol : 2;

            $usuario = Usuario::create([
                'nombre'           => $data['nombre'],
                'apellido'         => $data['apellido'],
                'nombre_usuario'   => $data['nombre_usuario'],
                'email'            => $data['email'],
                'password_hash'    => Hash::make($data['password']),
                'id_rol'           => $idRol,
                'genero'           => $data['genero'] ?? null,
                'id_localidad'     => $data['id_localidad'] ?? null,
                'id_pais'          => $data['id_pais'] ?? null,
                'domicilio'        => $data['domicilio'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'estado'           => 'Activo',
            ]);

            $estudiante = Estudiante::create([
                'id_usuario' => $usuario->id_usuario,
            ]);

            Dni::create([
                'id_usuario'  => $usuario->id_usuario,
                'dni'         => $data['dni'],
                'cuil'        => $data['cuil'] ?? null,
                'id_tipo_dni' => $data['id_tipo_dni']
            ]);

            if (!empty($data['telefono_celular'])) {
                Telefono::create([
                    'id_usuario' => $usuario->id_usuario,
                    'telefono'   => $data['telefono_celular'],
                ]);
            }



            return $estudiante->load([
                'usuario.localidad.pais',
                'usuario.rol',
                'usuario.dnis.tipoDni',
                'usuario.telefonos',
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    /**
     * Actualizar los datos de un estudiante.
     */
    public function update(int $idUsuario, array $data): Estudiante
    {
        return DB::transaction(function () use ($idUsuario, $data) {

            $estudiante = Estudiante::where('id_usuario', $idUsuario)->firstOrFail();
            $usuario    = $estudiante->usuario;

            // Campos del usuario
            $camposUsuario = array_filter([
                'nombre'           => $data['nombre']                 ?? null,
                'apellido'         => $data['apellido']               ?? null,
                'email'            => $data['email']                  ?? null,
                'nombre_usuario'   => $data['nombre_usuario']         ?? null,
                'id_localidad' => $data['id_localidad'] ?? null,
                'estado'           => $data['estado']                 ?? null,
                'genero'           => $data['genero']                 ?? null,
                'id_pais'          => $data['id_pais']                ?? null,
                'domicilio'        => $data['domicilio']               ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento']        ?? null,
            ], fn($v) => !is_null($v));

            if (!empty($data['password'])) {
                $camposUsuario['password_hash'] = Hash::make($data['password']);
            }

            if (!empty($camposUsuario)) {
                $usuario->update($camposUsuario);
            }

            // Actualizar DNI (eliminar el anterior y crear el nuevo)
            if (!empty($data['dni'])) {
                Dni::where('id_usuario', $idUsuario)->delete();
                Dni::create([
                    'id_usuario'  => $idUsuario,
                    'dni'         => $data['dni'],
                    'cuil'        => $data['cuil'] ?? null,
                    'id_tipo_dni' => $data['id_tipo_dni'],
                ]);
            }

            // Actualizar teléfono celular (posición 0)
            if (array_key_exists('telefono_celular', $data)) {
                $this->upsertTelefono($idUsuario, $data['telefono_celular'], 0);
            }


            return $estudiante->load([
                'usuario.localidad.pais',
                'usuario.rol',
                'usuario.dnis.tipoDni',
                'usuario.telefonos',
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // TOGGLE ESTADO
    // -------------------------------------------------------------------------

    public function toggleEstado(int $idUsuario): Estudiante
    {
        return DB::transaction(function () use ($idUsuario) {

            $estudiante = Estudiante::where('id_usuario', $idUsuario)->firstOrFail();
            $usuario    = $estudiante->usuario;

            $usuario->update([
                'estado' => $usuario->estado === 'Activo' ? 'Inactivo' : 'Activo',
            ]);

            return $estudiante->load([
                'usuario.rol',
                'usuario.localidad',
                'usuario.dnis',
                'usuario.telefonos',
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // HELPERS PRIVADOS
    // -------------------------------------------------------------------------

    /**
     * Insertar o actualizar un teléfono por posición (0 = celular, 1 = secundario).
     */
    private function upsertTelefono(int $idUsuario, ?string $numero, int $posicion): void
    {
        $telefonos = Telefono::where('id_usuario', $idUsuario)
            ->get()
            ->values();

        if ($telefonos->has($posicion)) {
            $telefonoActual = $telefonos->get($posicion)->telefono;

            if ($numero === $telefonoActual) {
                return;
            }

            Telefono::where('id_usuario', $idUsuario)
                ->where('telefono', $telefonoActual)
                ->delete();

            if ($numero) {
                Telefono::create([
                    'id_usuario' => $idUsuario,
                    'telefono'   => $numero,
                ]);
            }
        } elseif ($numero) {
            Telefono::create([
                'id_usuario' => $idUsuario,
                'telefono'   => $numero,
            ]);
        }
    }
    // -------------------------------------------------------------------------
    // EGRESADOS
    // Alumnos marcados como Egresado en estudiante_carrera (lo hace solo
    // MateriaService::registrarEgresoSiCorresponde cuando el analítico llega
    // al 100%), con filtro opcional por carrera, año de egreso y cohorte.
    // "Cohorte" = año en que entró a la carrera. "Año" = año en que egresó.
    // -------------------------------------------------------------------------

    public function buscarEgresados(?int $idCarrera = null, ?int $anio = null, ?int $cohorte = null): Collection
    {
        $estadoEgresado = EstadoCarrera::firstOrCreate(['estado' => 'Egresado']);

        $query = EstudianteCarrera::with(['estudiante.usuario', 'estudiante.usuario.dnis', 'carrera'])
            ->where('id_estado_carrera', $estadoEgresado->id_estado_carrera)
            ->whereNotNull('fecha_egreso');

        if ($idCarrera) {
            $query->where('id_carrera', $idCarrera);
        }

        if ($anio) {
            $query->whereYear('fecha_egreso', $anio);
        }

        if ($cohorte) {
            $query->whereYear('cohorte', $cohorte);
        }

        $egresados = $query->orderByDesc('fecha_egreso')->get();

        foreach ($egresados as $egresado) {
            $promedios = NotaCuatrimestre::where('id_usuario', $egresado->id_usuario)
                ->where('periodo', 'CIERRE')
                ->whereHas('materia', fn ($q) => $q->where('id_carrera', $egresado->id_carrera))
                ->pluck('valor');

            $egresado->promedio = $promedios->isNotEmpty() ? round((float) $promedios->avg(), 2) : null;
        }

        return $egresados;
    }

    /**
     * Años y cohortes disponibles para los selects de filtro de Egresados.
     */
    public function opcionesFiltroEgresados(): array
    {
        $estadoEgresado = EstadoCarrera::firstOrCreate(['estado' => 'Egresado']);

        $base = EstudianteCarrera::where('id_estado_carrera', $estadoEgresado->id_estado_carrera)
            ->whereNotNull('fecha_egreso')
            ->get();

        $anios = $base->pluck('fecha_egreso')->filter()->map(fn ($f) => $f->format('Y'))->unique()->sortDesc()->values();
        $cohortes = $base->pluck('cohorte')->filter()->map(fn ($f) => $f->format('Y'))->unique()->sortDesc()->values();

        return ['anios' => $anios, 'cohortes' => $cohortes];
    }
}
