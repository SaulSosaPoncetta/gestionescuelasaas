<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Services\EstudianteService;
use App\Services\AuditoriaService;
use App\Services\MateriaService;
use App\Services\PersonaService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EstudianteController extends Controller
{
    protected EstudianteService $estudianteService;
    protected AuditoriaService $auditoriaService;
    protected MateriaService $materiaService;
    protected PersonaService $personaService;

    public function __construct(
        EstudianteService $estudianteService,
        AuditoriaService $auditoriaService,
        MateriaService $materiaService,
        PersonaService $personaService
    ) {
        $this->estudianteService = $estudianteService;
        $this->auditoriaService = $auditoriaService;
        $this->materiaService = $materiaService;
        $this->personaService = $personaService;
    }

    // -------------------------------------------------------------------------
    // GET /buscarAlumno
    // Lista de alumnos con filtros opcionales
    // -------------------------------------------------------------------------
    public function index(Request $request)
    {
        $filtros = $request->only(['nombre_apellido', 'dni', 'anio', 'padron']);

        $estudiantes = $this->estudianteService->buscar($filtros);

        return view('Secretaria.alumnosSec', compact('estudiantes', 'filtros'));
    }

    // -------------------------------------------------------------------------
    // GET /alumnoAgregar
    // Formulario para crear un nuevo alumno
    // -------------------------------------------------------------------------
    public function create()
    {
        $tiposDni    = $this->estudianteService->getTiposDni();
        $paises      = $this->estudianteService->getPaises();
        $localidades = $this->estudianteService->getLocalidades();

        return view('Secretaria.alumnoAgregar', compact('tiposDni', 'paises', 'localidades'));
    }

    // -------------------------------------------------------------------------
    // POST /alumnoAgregar
    // Guarda un nuevo alumno (AJAX JSON)
    // Si el DNI ya pertenece a una persona registrada (ej. un docente),
    // no se crea otro usuario: se le agrega el rol de alumno.
    // -------------------------------------------------------------------------
    public function store(Request $request): JsonResponse
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
            'id_tipo_dni'      => 'required|integer|exists:tipos_dni,id_tipo_dni',
            'telefono_celular' => 'nullable|numeric|digits_between:10,13',
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

            // Que no sea ya alumno
            if (Estudiante::where('id_usuario', $existente->id_usuario)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación.',
                    'errors'  => ['dni' => ['Esta persona ya está registrada como alumno.']],
                ], 422);
            }

            // Solo se agrega el rol. Si tu tabla "estudiantes" exige más columnas
            // (padrón, año de ingreso, etc.), agregalas acá.
            $estudiante = Estudiante::create([
                'id_usuario' => $existente->id_usuario,
            ]);
            $estudiante->load('usuario');
        } else {
            $estudiante = $this->estudianteService->create($datos);
        }

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Alumnos',
            accion: 'crear',
            descripcion: ($existente ? 'Asignó el rol de alumno a ' : 'Creó al alumno ')
                . "{$estudiante->usuario->apellido}, {$estudiante->usuario->nombre}",
            nombreTabla: 'estudiantes',
            idRegistro: $estudiante->id_usuario
        );

        return response()->json([
            'success' => true,
            'message' => $existente
                ? 'Alumno creado correctamente. Se utilizó la cuenta existente de la persona.'
                : 'Alumno creado correctamente.',
            'data'    => $estudiante,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /alumnoPerfil/{id}
    // Muestra el perfil completo de un alumno
    // -------------------------------------------------------------------------
    public function show(int $id)
    {
        $estudiante = $this->estudianteService->getById($id);

        // DNI principal (primer registro)
        $dniRegistro     = $estudiante->usuario->dnis->first();
        $dniNumero       = $dniRegistro?->dni   ?? '—';
        $dniCuil         = $dniRegistro?->cuil  ?? '';
        $tipoDni         = $dniRegistro?->tipoDni?->descripcion ?? '—';
        $idTipoDni       = $dniRegistro?->id_tipo_dni ?? null;

        // Teléfonos
        $tels            = $estudiante->usuario->telefonos->values();
        $telefonos = [
            'celular'    => $tels->get(0)?->telefono ?? '',
        ];

        $localidades = $this->estudianteService->getLocalidades();
        $tiposDni    = $this->estudianteService->getTiposDni();
        $paises      = $this->estudianteService->getPaises();

        return view('Secretaria.alumnoPerfil', compact(
            'estudiante',
            'dniNumero',
            'dniCuil',
            'tipoDni',
            'idTipoDni',
            'telefonos',
            'localidades',
            'tiposDni',
            'paises'
        ));
    }

    // -------------------------------------------------------------------------
    // PUT /alumnoPerfil/{id}
    // Actualiza los datos del alumno (AJAX)
    // -------------------------------------------------------------------------
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre'   => 'required|string|max:100|regex:/^[\pL\s\-]+$/u',
            'apellido' => 'required|string|max:100|regex:/^[\pL\s\-]+$/u',
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('usuarios', 'email')->ignore($id, 'id_usuario'),
            ],
            'nombre_usuario' => [
                'required', 'string', 'max:50',
                Rule::unique('usuarios', 'nombre_usuario')->ignore($id, 'id_usuario'),
            ],
            'password' => ['nullable', Password::min(8)->mixedCase()->numbers()],
            'id_localidad' => 'required|integer|exists:localidades,id_localidad',
            'estado'                 => 'nullable|in:Activo,Inactivo',
            'telefono_celular'       => 'nullable|numeric|digits_between:10,13',
            'genero'           => 'nullable|in:M,F,Otro',
            'id_pais'          => 'nullable|integer|exists:paises,id_pais',
            'domicilio'        => 'nullable|string|max:255',
            'fecha_nacimiento' => 'required|date|before_or_equal:-18 years|after:-120 years',
            'dni' => [
                'nullable', 'digits_between:7,8',
                Rule::unique('dnis', 'dni')->where(function ($query) use ($id) {
                    return $query->where('id_usuario', '!=', $id);
                }),
            ],
            'cuil' => [
                'nullable',
                'regex:/^\d{2}-\d{8}-\d$/',
                Rule::unique('dnis', 'cuil')->where(function ($query) use ($id) {
                    return $query->where('id_usuario', '!=', $id);
                }),
            ],
            'id_tipo_dni' => 'nullable|integer|exists:tipos_dni,id_tipo_dni',
        ], [
            'dni.unique'   => 'Ese DNI ya está registrado.',
            'dni.digits_between' => 'El DNI debe tener entre 7 y 8 dígitos.',
            'cuil.regex'   => 'El CUIL/CUIT debe tener el formato 20-12345678-3.',
            'cuil.unique'  => 'Ese CUIL/CUIT ya está registrado.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $estudiante = $this->estudianteService->update($id, $validator->validated());

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Alumnos',
            accion: 'actualizar',
            descripcion: "Actualizó los datos del alumno {$estudiante->usuario->apellido}, {$estudiante->usuario->nombre}",
            nombreTabla: 'estudiantes',
            idRegistro: $estudiante->id_usuario
        );

        return response()->json([
            'success' => true,
            'message' => 'Datos del alumno actualizados correctamente.',
            'data'    => $estudiante,
        ]);
    }

    // -------------------------------------------------------------------------
    // PATCH /alumnos/{id}/estado
    // Habilita o deshabilita un alumno (toggle)
    // -------------------------------------------------------------------------
    public function toggleEstado(int $id): JsonResponse
    {
        $estudiante = $this->estudianteService->toggleEstado($id);
        $estado     = $estudiante->usuario->estado;

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Alumnos',
            accion: $estado === 'Activo' ? 'habilitar' : 'deshabilitar',
            descripcion: ($estado === 'Activo' ? 'Habilitó' : 'Deshabilitó') . " al alumno {$estudiante->usuario->apellido}, {$estudiante->usuario->nombre}",
            nombreTabla: 'estudiantes',
            idRegistro: $estudiante->id_usuario
        );

        return response()->json([
            'success' => true,
            'message' => "Alumno {$estado} correctamente.",
            'estado'  => $estado,
            'data'    => $estudiante,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /misAnalitico
    // Analítico del alumno logueado (versión "propia" de analitico()).
    // -------------------------------------------------------------------------
    public function analiticoPropio(Request $request)
    {
        return $this->analiticoView(auth()->id(), $request);
    }

    public function analitico(int $id, Request $request)
    {
        return $this->analiticoView($id, $request);
    }

    private function analiticoView(int $id, Request $request)
    {
        $estudiante = $this->estudianteService->getById($id);

        $carreras   = $this->estudianteService->getCarreras($id);
        $idCarrera  = $request->integer('carrera') ?: null;

        // Seguridad: la carrera pedida tiene que ser una del alumno
        if ($idCarrera && !$carreras->contains('id_carrera', $idCarrera)) {
            abort(404);
        }

        $analitico       = $this->materiaService->analiticoPorEstudiante($estudiante, $idCarrera);
        $carreraActual   = $analitico['carrera'];
        $materiasPorAnio = $analitico['materiasPorAnio'];

        return view('Secretaria.analitico', compact(
            'estudiante', 'carreraActual', 'materiasPorAnio', 'carreras'
        ));
    }
}