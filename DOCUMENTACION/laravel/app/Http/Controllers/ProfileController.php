<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Services\ProfileService;
use App\Models\Usuario;
use App\Services\AuditoriaService;

class ProfileController extends Controller
{
    protected ProfileService $profileService;
    protected AuditoriaService $auditoriaService;

    public function __construct(AuditoriaService $auditoriaService)
{

    $this->profileService = new ProfileService();
    $this->auditoriaService = $auditoriaService;
}

    // -------------------------------------------------------------------------
    // GET /perfil
    // Muestra los datos del usuario autenticado
    // -------------------------------------------------------------------------
    public function show()
    {
        $usuario     = $this->profileService->getPerfil();
        $paises      = \App\Models\Pais::orderBy('nombre_pais')->get();
        $localidades = \App\Models\Localidad::orderBy('nombre_localidad')->get();
        $tiposDni    = \App\Models\TipoDni::orderBy('descripcion')->get();

        return view('Secretaria.misDatos', compact('usuario', 'paises', 'localidades', 'tiposDni'));
    }

    // -------------------------------------------------------------------------
    // PUT /perfil
    // Administrador: puede editar todos los campos.
    // Docente / Estudiante: solo email, telefonos y domicilio.
    // -------------------------------------------------------------------------
    public function update(Request $request): JsonResponse
{
    $usuario = Auth::user();
    $id      = $usuario->id_usuario;

    $rules = [
        'email' => [
            'required', 'email', 'max:150',
            Rule::unique('usuarios', 'email')->ignore($id, 'id_usuario'),
        ],
        'telefonos' => 'nullable|numeric|digits_between:10,13',
        'domicilio'=> 'nullable|string|max:255',
    ];

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        $this->auditoriaService->registrar(
            actor: Auth::user(),
            categoria: 'Perfil',
            accion: 'actualizar',
            descripcion: 'Actualizó sus propios datos de perfil',
            nombreTabla: 'usuarios',
            idRegistro: $id
        );

        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
        ], 422);
    }

    $usuarioActualizado = $this->profileService->updatePerfil($usuario, $validator->validated());

    return response()->json([
        'success' => true,
        'message' => 'Datos actualizados correctamente.',
        'data'    => $usuarioActualizado,
    ]);
}

    /**
     * Solo Administrador puede editar todos los campos del perfil.
     * Docente y Estudiante quedan limitados a email, telefonos y domicilio.
     */
    private function puedeEditarTodo(Usuario $usuario): bool
    {
        $usuario->loadMissing('rol');
        return $usuario->rol?->descripcion === 'Administrador';
    }

    public function showEstudiante()
    {
        $usuario     = $this->profileService->getPerfil();
        $paises      = \App\Models\Pais::orderBy('nombre_pais')->get();
        $localidades = \App\Models\Localidad::orderBy('nombre_localidad')->get();
        $tiposDni    = \App\Models\TipoDni::orderBy('descripcion')->get();

        return view('Estudiante.misDatos', compact('usuario', 'paises', 'localidades', 'tiposDni'));
    }

    public function showDocente()
    {
        $usuario     = $this->profileService->getPerfil();
        $paises      = \App\Models\Pais::orderBy('nombre_pais')->get();
        $localidades = \App\Models\Localidad::orderBy('nombre_localidad')->get();
        $tiposDni    = \App\Models\TipoDni::orderBy('descripcion')->get();

        return view('Docente.misDatos', compact('usuario', 'paises', 'localidades', 'tiposDni'));
    }
}