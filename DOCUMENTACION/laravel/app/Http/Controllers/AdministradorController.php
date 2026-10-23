<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Services\AdministradorService;
use App\Services\AuditoriaService;
use App\Services\PersonaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdministradorController extends Controller
{
    protected AdministradorService $administradorService;
    protected AuditoriaService $auditoriaService;
    protected PersonaService $personaService;

    public function __construct(
        AdministradorService $administradorService,
        AuditoriaService $auditoriaService,
        PersonaService $personaService
    ) {
        $this->administradorService = $administradorService;
        $this->auditoriaService = $auditoriaService;
        $this->personaService = $personaService;
    }

    public function create()
    {
        $tiposDni    = $this->administradorService->getTiposDni();
        $localidades = $this->administradorService->getLocalidades();
        $paises      = $this->administradorService->getPaises();

        return view('Secretaria.administracionAgregar', compact('tiposDni', 'localidades', 'paises'));
    }

    public function index(Request $request)
    {
        $filtros         = $request->only(['nombre_apellido', 'dni', 'estado']);
        $administradores = $this->administradorService->buscar($filtros);

        return view('Secretaria.administracionSec', compact('administradores', 'filtros'));
    }

    public function show(int $id)
    {
        $administrador = $this->administradorService->getById($id);
        $telefonos     = $administrador->usuario->telefonos;
        $dni           = $administrador->usuario->dnis;
        $localidades   = $this->administradorService->getLocalidades();
        $paises        = $this->administradorService->getPaises();
        $tiposDni      = $this->administradorService->getTiposDni();

        $tipoDocumento = $dni->first() ? $dni->first()->tipoDni->descripcion : '—';

        return view('Secretaria.administracionPerfil', compact(
            'administrador',
            'telefonos',
            'dni',
            'localidades',
            'paises',
            'tiposDni',
            'tipoDocumento'
        ));
    }

    /**
     * Si el DNI ya pertenece a una persona registrada (ej. un docente),
     * no se crea otro usuario: se le agrega el rol de administrador.
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
                'errors'  => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();

        if ($existente) {
            // Seguridad: el e-mail tiene que coincidir con el de la persona registrada
            if (strcasecmp($datos['email'], $existente->email) !== 0) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['email' => ['Ese DNI ya pertenece a una persona registrada con otro e-mail.']],
                ], 422);
            }

            // Que no sea ya administrador
            if (Administrador::where('id_usuario', $existente->id_usuario)->exists()) {
                return response()->json([
                    'success' => false,
                    'errors'  => ['dni' => ['Esta persona ya está registrada como administrador.']],
                ], 422);
            }

            // Solo se agrega el rol. Si tu tabla "administradores" exige más columnas,
            // agregalas acá.
            $administrador = Administrador::create([
                'id_usuario' => $existente->id_usuario,
            ]);
            $administrador->load('usuario');
        } else {
            $administrador = $this->administradorService->create($datos);
        }

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Administradores',
            accion: 'crear',
            descripcion: ($existente ? 'Asignó el rol de administrador a ' : 'Creó al administrador ')
                . "{$administrador->usuario->apellido}, {$administrador->usuario->nombre}",
            nombreTabla: 'administradores',
            idRegistro: $administrador->id_usuario
        );

        return response()->json([
            'success' => true,
            'message' => $existente
                ? 'Administrador creado correctamente. Se utilizó la cuenta existente de la persona.'
                : 'Administrador creado correctamente.',
            'data'    => $administrador,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre'   => 'required|string|max:100|regex:/^[\pL\s\-]+$/u',
            'apellido' => 'required|string|max:100|regex:/^[\pL\s\-]+$/u',
            'email'            => [
                'required', 'email', 'max:150',
                Rule::unique('usuarios', 'email')->ignore($id, 'id_usuario'),
            ],
            'nombre_usuario'   => [
                'required', 'string', 'max:50',
                Rule::unique('usuarios', 'nombre_usuario')->ignore($id, 'id_usuario'),
            ],
            'password' => ['nullable', Password::min(8)->mixedCase()->numbers()],
            'genero'           => 'nullable|in:M,F,Otro',
            'id_localidad'     => 'nullable|integer|exists:localidades,id_localidad',
            'dni'              => [
                'required', 'digits_between:7,8',
                Rule::unique('dnis', 'dni')->where(fn($q) => $q->where('id_usuario', '!=', $id)),
            ],
            'cuil'             => [
                'nullable',
                'regex:/^\d{2}-\d{8}-\d$/',
                Rule::unique('dnis', 'cuil')->where(fn($q) => $q->where('id_usuario', '!=', $id)),
            ],
            'id_tipo_dni'      => 'required|integer|exists:tipos_dni,id_tipo_dni',
            'telefono'         => 'nullable|numeric|digits_between:10,13',
            'id_pais'          => 'nullable|integer|exists:paises,id_pais',
            'domicilio'        => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'estado'           => 'sometimes|in:Activo,Inactivo',
        ], [
            'dni.digits_between' => 'El DNI debe tener entre 7 y 8 dígitos.',
            'dni.unique'         => 'Ese DNI ya está registrado.',
            'cuil.regex'         => 'El CUIL/CUIT debe tener el formato 20-12345678-3.',
            'cuil.unique'        => 'Ese CUIL/CUIT ya está registrado.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $administrador = $this->administradorService->update($id, $validator->validated());

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Administradores',
            accion: 'actualizar',
            descripcion: "Actualizó los datos del administrador {$administrador->usuario->apellido}, {$administrador->usuario->nombre}",
            nombreTabla: 'administradores',
            idRegistro: $administrador->id_usuario
        );

        return response()->json([
            'success' => true,
            'message' => 'Administrador actualizado correctamente.',
            'data'    => $administrador,
        ]);
    }

    public function toggleEstado(int $id): JsonResponse
    {
        $administrador = $this->administradorService->toggleEstado($id);
        $estado = $administrador->usuario->estado;

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Administradores',
            accion: $estado === 'Activo' ? 'habilitar' : 'deshabilitar',
            descripcion: ($estado === 'Activo' ? 'Habilitó' : 'Deshabilitó') . " al administrador {$administrador->usuario->apellido}, {$administrador->usuario->nombre}",
            nombreTabla: 'administradores',
            idRegistro: $administrador->id_usuario
        );

        return response()->json([
            'success' => true,
            'message' => "Administrador {$estado} correctamente.",
            'estado'  => $estado,
            'data'    => $administrador,
        ]);
    }
}