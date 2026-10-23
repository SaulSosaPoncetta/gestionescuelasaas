<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateDocenteRequest;
use App\Models\Usuario;
use App\Models\Docente;
use App\Services\DocenteService;
use App\Services\AuditoriaService;
use App\Services\PersonaService;
use App\Models\Pais;
use App\Models\Dni;
use App\Models\Telefono;
use App\Models\Localidad;
use App\Models\TipoDni;
use App\Models\Materia;
use App\Models\MateriaDocente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DocenteController extends Controller
{

    protected $docenteService;
    protected AuditoriaService $auditoriaService;
    protected PersonaService $personaService;

    public function __construct(
        DocenteService $docenteService,
        AuditoriaService $auditoriaService,
        PersonaService $personaService
    )
    {
        $this->docenteService = $docenteService;
        $this->auditoriaService = $auditoriaService;
        $this->personaService = $personaService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filtros = $request->only([
            'nombre_apellido',
            'dni',
            'estado',
            'tipo',
            'plan',
            'desde',
            'hasta'
        ]);

        $docentes = $this->docenteService->buscar($filtros);

        return view('Secretaria.profesoresSec', compact('docentes', 'filtros'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tiposDni = TipoDni::all();
        $paises = Pais::all();
        $localidades = Localidad::all();

        return view(
            'Secretaria.profesoresAgregar',
            compact(
                'tiposDni',
                'paises',
                'localidades'
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * Si el DNI ya pertenece a una persona registrada (por ejemplo, un alumno),
     * no se crea otro usuario: se le agrega el rol de docente.
     */
    public function store(Request $request)
    {
        // ¿Ya existe esta persona?
        $existente   = $this->personaService->buscarPorDni($request->input('dni'));
        $idExistente = $existente?->id_usuario;

        $reglas = [
            'nombre'   => 'required|string|max:100|regex:/^[\pL\s\-]+$/u',
            'apellido' => 'required|string|max:100|regex:/^[\pL\s\-]+$/u',
            'nombre_usuario' => 'required|string|max:50|unique:usuarios,nombre_usuario',
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('usuarios', 'email')->ignore($idExistente, 'id_usuario'),
            ],
            'password'         => ['required', Password::min(8)->mixedCase()->numbers()],
            'genero'           => 'nullable|in:M,F,Otro',
            'id_localidad'     => 'required|integer|exists:localidades,id_localidad',
            'id_pais'          => 'required|integer|exists:paises,id_pais',
            'domicilio'        => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date|before_or_equal:-18 years|after:-120 years',
            'dni'              => 'required|digits_between:7,8', // sin unique: puede existir con otro rol
            'cuil' => [
                'nullable', 'regex:/^\d{2}-\d{8}-\d$/',
                Rule::unique('dnis', 'cuil')->where(fn ($q) => $q->where('id_usuario', '!=', $idExistente ?? 0)),
            ],
            'id_tipo_dni' => 'required|integer|exists:tipos_dni,id_tipo_dni',
            'telefono'    => 'nullable|numeric|digits_between:10,13',
        ];

        // Si la persona ya tiene cuenta, el usuario y la contraseña no se tocan
        if ($existente) {
            unset($reglas['nombre_usuario'], $reglas['password']);
        }

        $validator = Validator::make($request->all(), $reglas, [
            'dni.required'       => 'Debe ingresar un DNI.',
            'dni.digits_between' => 'El DNI debe tener entre 7 y 8 dígitos.',
            'cuil.regex'         => 'El CUIL/CUIT debe tener el formato 20-12345678-3.',
            'cuil.unique'        => 'Ese CUIL/CUIT ya está registrado.',
            'email.unique'       => 'Ese e-mail ya está registrado.',
            'fecha_nacimiento.before_or_equal' => 'El usuario debe ser mayor de 18 años.',
            'fecha_nacimiento.after'           => 'La fecha de nacimiento no es válida.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();

        if ($existente) {
            // Seguridad: el e-mail tiene que coincidir con el de la persona registrada
            if (strcasecmp($datos['email'], $existente->email) !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación.',
                    'errors'  => ['email' => ['Ese DNI ya pertenece a una persona registrada con otro e-mail.']],
                ], 422);
            }

            // Que no sea ya docente
            if (Docente::where('id_usuario', $existente->id_usuario)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación.',
                    'errors'  => ['dni' => ['Esta persona ya está registrada como docente.']],
                ], 422);
            }
        }

        $usuarioCreado = DB::transaction(function () use ($datos, $existente) {

            if ($existente) {
                $usuario = $existente;
            } else {
                $usuario = Usuario::create([
                    'nombre'           => $datos['nombre'],
                    'apellido'         => $datos['apellido'],
                    'nombre_usuario'   => $datos['nombre_usuario'],
                    'email'            => $datos['email'],
                    'password_hash'    => Hash::make($datos['password']),
                    'id_rol'           => 1,
                    'genero'           => $datos['genero'] ?? null,
                    'estado'           => 'Activo',
                    'id_localidad'     => $datos['id_localidad'],
                    'id_pais'          => $datos['id_pais'],
                    'fecha_nacimiento' => $datos['fecha_nacimiento'],
                    'domicilio'        => $datos['domicilio'],
                ]);

                Dni::create([
                    'dni'         => $datos['dni'],
                    'cuil'        => $datos['cuil'] ?? null,
                    'id_usuario'  => $usuario->id_usuario,
                    'id_tipo_dni' => $datos['id_tipo_dni'],
                ]);

                if (!empty($datos['telefono'])) {
                    Telefono::create([
                        'telefono'   => $datos['telefono'],
                        'id_usuario' => $usuario->id_usuario,
                    ]);
                }
            }

            Docente::create([
                'id_usuario' => $usuario->id_usuario,
            ]);

            return $usuario;
        });

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Docentes',
            accion: 'crear',
            descripcion: ($existente ? 'Asignó el rol de docente a ' : 'Creó al docente ')
                . "{$usuarioCreado->apellido}, {$usuarioCreado->nombre}",
            nombreTabla: 'docentes',
            idRegistro: $usuarioCreado->id_usuario
        );

        return response()->json([
            'success' => true,
            'message' => $existente
                ? 'Profesor agregado correctamente. Se utilizó la cuenta existente de la persona.'
                : 'Profesor agregado correctamente.',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $docente = Docente::with([
                'usuario',
                // 'usuario.dnis',
                'usuario.dnis.tipoDni',
                'usuario.telefonos',
                'usuario.localidad.pais',
                'materias.carrera',
                'materias.tipoMateria',
        ])->findOrFail($id);

        $tiposDni = TipoDni::all();
        $paises = Pais::all();
        $localidades = Localidad::all();

        return view(
            'Secretaria.profesoresPerfil',
            compact('docente', 'tiposDni', 'paises', 'localidades')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDocenteRequest $request, string $id)
    {
        $docente = Docente::with('usuario')->findOrFail($id);
        $datos   = $request->validated();

        DB::transaction(function () use ($datos, $docente) {

            $docente->usuario->update([
                'apellido'         => $datos['apellido'],
                'nombre'           => $datos['nombre'],
                'genero'           => $datos['genero'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
                'domicilio'        => $datos['domicilio'] ?? null,
                'email'            => $datos['email'],
                'nombre_usuario'   => $datos['nombre_usuario'],
                'id_localidad'     => $datos['id_localidad'] ?? null,
                'id_pais'          => $datos['id_pais'] ?? null,
                'estado'           => $datos['estado'] ?? $docente->usuario->estado,
            ]);

            if (!empty($datos['password'])) {
                $docente->usuario->update([
                    'password_hash' => Hash::make($datos['password']),
                ]);
            }

            $telefono = $docente->usuario->telefonos->first();

            if (!empty($datos['telefono'])) {
                if ($telefono) {
                    Telefono::where('telefono', $telefono->telefono)
                        ->where('id_usuario', $telefono->id_usuario)
                        ->update(['telefono' => $datos['telefono']]);
                } else {
                    Telefono::create([
                        'telefono'   => $datos['telefono'],
                        'id_usuario' => $docente->usuario->id_usuario,
                    ]);
                }
            }

            $dni = $docente->usuario->dnis->first();

            if ($dni) {
                Dni::where('dni', $dni->dni)
                    ->where('id_usuario', $dni->id_usuario)
                    ->delete();
            }

            Dni::create([
                'dni'         => $datos['dni'],
                'cuil'        => $datos['cuil'] ?? null,
                'id_usuario'  => $docente->usuario->id_usuario,
                'id_tipo_dni' => $datos['id_tipo_dni'],
            ]);
        });

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Docentes',
            accion: 'actualizar',
            descripcion: "Actualizó los datos del docente {$docente->usuario->apellido}, {$docente->usuario->nombre}",
            nombreTabla: 'docentes',
            idRegistro: $docente->usuario->id_usuario
        );

        return response()->json([
            'success' => true,
            'message' => 'Profesor actualizado correctamente.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function toggleEstado(int $id): JsonResponse
    {
        $docente = $this->docenteService->toggleEstado($id);
        $estado = $docente->usuario->estado;

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Docentes',
            accion: $estado === 'Activo' ? 'habilitar' : 'deshabilitar',
            descripcion: ($estado === 'Activo' ? 'Habilitó' : 'Deshabilitó') . " al docente {$docente->usuario->apellido}, {$docente->usuario->nombre}",
            nombreTabla: 'docentes',
            idRegistro: $docente->usuario->id_usuario
        );

        return response()->json([
            'success' => true,
            'message' => "Docente {$estado} correctamente.",
            'estado'  => $estado,
            'data'    => $docente,
        ]);
    }
}