<?php

namespace App\Http\Controllers;

use App\Services\PlanEstudioService;
use App\Services\AuditoriaService;
use App\Models\Carrera;
use App\Models\PlanResolucion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PlanEstudioController extends Controller
{
    protected PlanEstudioService $planEstudioService;
    protected AuditoriaService $auditoriaService;

    public function __construct(PlanEstudioService $planEstudioService, AuditoriaService $auditoriaService)
    {
        $this->planEstudioService = $planEstudioService;
        $this->auditoriaService = $auditoriaService;
    }

    public function index(Request $request)
    {
        $filtro = $request->get('nombre');

        $planResoluciones = $this->planEstudioService->buscarPlanResoluciones($filtro);

        return view('Secretaria.planDeEstudio', compact('planResoluciones'));
    }
    public function espacios(Request $request, Carrera $carrera)
    {
        $filtro = $request->get('nombre');
        $materias = $this->planEstudioService->buscarEspaciosPorCarrera($carrera, $filtro);
        $planResolucion = PlanResolucion::where('id_carrera', $carrera->id_carrera)->first();
        return view('Secretaria.planEspacios', compact('carrera', 'materias','planResolucion'));
    }

    public function create()
    {
        $carreraSinAsignar = Carrera::sinAsignar();
        $carreras = Carrera::where('id_carrera', '!=', $carreraSinAsignar->id_carrera)
            ->orderBy('nombre')
            ->get();

        return view('Secretaria.planEstudioAgregar', compact('carreras', 'carreraSinAsignar'));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'titulo'        => ['required', 'string', 'max:150', 'regex:/^[\pL\d\s]+$/u'],
            'plan_descripcion'        => 'nullable|string|max:255',
            'resolucion_descripcion'  => 'required|string|max:150',
            'id_carrera'              => 'nullable|integer|exists:carreras,id_carrera',
            'total_horas'             => 'required|integer|min:1',
            'fecha'                   => 'required|date',
            'archivo_descripcion'     => 'required|string|max:150',
            'archivo'                 => 'required|file|mimes:pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();

        $planResolucion = $this->planEstudioService->crearPlanCompleto(
            $validator->safe()->except('archivo'),
            $request->file('archivo')
        );

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Planes de estudio',
            accion: 'crear',
            descripcion: "Creó el plan de estudio \"{$datos['titulo']}\"",
            nombreTabla: 'plan_resolucion',
            idRegistro: $planResolucion->id ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Plan de estudio creado y asignado correctamente.',
            'data'    => $planResolucion,
        ], 201);
    }

    public function edit(int $id)
{
    $planResolucion = $this->planEstudioService->buscarPorId($id);
    $carreras       = Carrera::orderBy('nombre')->get();

    return view('Secretaria.planEstudioModificar', compact('planResolucion', 'carreras'));
}

public function update(Request $request, int $id): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'titulo'        => ['required', 'string', 'max:150', 'regex:/^[\pL\d\s]+$/u'],
        'plan_descripcion'        => 'nullable|string|max:255',
        'resolucion_descripcion'  => 'required|string|max:150',
        'id_carrera'              => 'nullable|integer|exists:carreras,id_carrera',
        'total_horas'             => 'required|integer|min:1',
        'fecha'                   => 'required|date',
        'archivo_descripcion'     => 'required|string|max:150',
        'archivo'                 => 'nullable|file|mimes:pdf|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $datos = $validator->validated();

    $planResolucion = $this->planEstudioService->actualizarPlanCompleto(
        $id,
        $validator->safe()->except('archivo'),
        $request->file('archivo')
    );

    $this->auditoriaService->registrar(
        actor: auth()->user(),
        categoria: 'Planes de estudio',
        accion: 'actualizar',
        descripcion: "Actualizó el plan de estudio \"{$datos['titulo']}\"",
        nombreTabla: 'plan_resolucion',
        idRegistro: $planResolucion->id ?? $id
    );

    return response()->json([
        'success' => true,
        'message' => 'Plan de estudio actualizado correctamente.',
        'data'    => $planResolucion,
    ]);
}
public function perfil(int $id)
{
    $planResolucion = $this->planEstudioService->buscarPorId($id);
    $carreras       = Carrera::orderBy('nombre')->get();

    return view('Secretaria.planEstudioPerfil', compact('planResolucion', 'carreras'));
}
public function show(int $id)
{
    $planResolucion = $this->planEstudioService->buscarPorId($id);

    return view('Estudiante.planEstudioPerfil', compact('planResolucion'));
}
// -------------------------------------------------------------------------
// GET /plan-estudio/{carrera}/alumnos
// Alumnos matriculados en la carrera del plan de estudio, con filtro
// opcional por materia (que la estén cursando/tengan cursada) y por año
// de cohorte, y su promedio (finales + cierres promocionados).
// -------------------------------------------------------------------------
public function alumnos(Request $request, Carrera $carrera)
    {
        $carrera->load('planVigente.plan');

        // Todas las filas de materias (una por ciclo). Se usan para el promedio.
        $todasLasMaterias = $carrera->materias()->orderBy('anio')->orderBy('nombre')->get();
        $idsMaterias      = $todasLasMaterias->pluck('id_materia');

        // Para el select: cada nombre una sola vez.
        $materias = $todasLasMaterias->unique('nombre')->values();

        $anios = $carrera->estudiantes()
            ->get()
            ->pluck('pivot.cohorte')
            ->filter()
            ->map(fn ($cohorte) => \Illuminate\Support\Carbon::parse($cohorte)->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        $filtroMateria = $request->get('materia');   // ahora es el NOMBRE
        $filtroAnio    = $request->get('anio');

        $query = $carrera->estudiantes()->with(['usuario.dnis']);

        if ($filtroAnio) {
            $query->wherePivot('cohorte', 'like', $filtroAnio . '%');
        }

        if ($filtroMateria) {
            $query->whereHas('materias', function ($q) use ($filtroMateria, $carrera) {
                $q->where('materias.nombre', $filtroMateria)
                ->where('materias.id_carrera', $carrera->id_carrera);
            });
        }

        $estudiantes = $query->get()
            ->sortBy(fn ($est) => $est->usuario->apellido . $est->usuario->nombre)
            ->values()
            ->map(function ($estudiante) use ($idsMaterias) {
                $notas = \App\Models\NotaFinal::where('id_usuario', $estudiante->id_usuario)
                    ->whereIn('id_materia', $idsMaterias)
                    ->pluck('valor')
                    ->merge(
                        \App\Models\NotaCuatrimestre::where('id_usuario', $estudiante->id_usuario)
                            ->where('periodo', 'CIERRE')
                            ->where('situacion', 'promocionado')
                            ->whereIn('id_materia', $idsMaterias)
                            ->pluck('valor')
                    );

                $estudiante->promedio = $notas->isNotEmpty() ? round((float) $notas->avg(), 2) : null;

                return $estudiante;
            });

        return view('Secretaria.planAlumnos', compact('carrera', 'materias', 'anios', 'estudiantes', 'filtroMateria', 'filtroAnio'));
}
}
