<?php

namespace App\Http\Controllers;

use App\Models\PlanEstudio;
use App\Models\ResolucionCarrera;
use App\Services\CarreraService;
use App\Services\MateriaService;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\TipoArchivo;

class CarreraController extends Controller
{
    protected CarreraService $carreraService;
    protected MateriaService $materiaService;
    protected AuditoriaService $auditoriaService;

    public function __construct(CarreraService $carreraService, MateriaService $materiaService, AuditoriaService $auditoriaService)
    {
        $this->carreraService = $carreraService;
        $this->materiaService = $materiaService;
        $this->auditoriaService = $auditoriaService;
    }

    public function index(Request $request)
    {
        $filtros   = $request->only(['nombre']);
        $carreras  = $this->carreraService->buscar($filtros);

        return view('Secretaria.carreraSec', compact('carreras', 'filtros'));
    }

    public function create()
    {
        // Catálogos existentes para los selects de "Plan de estudio"
        $planesEstudio = PlanEstudio::orderBy('titulo')->get();
        $resoluciones  = ResolucionCarrera::orderBy('descripcion')->get();

        return view('Secretaria.carreraAgregar', compact('planesEstudio', 'resoluciones'));
    }

    public function edit(int $id)
    {
        $carrera       = $this->carreraService->getById($id);
        $planesEstudio = PlanEstudio::orderBy('titulo')->get();
        $resoluciones  = ResolucionCarrera::orderBy('descripcion')->get();

        return view('Secretaria.carreraEditar', compact('carrera', 'planesEstudio', 'resoluciones'));
    }

   public function show(Request $request, int $id)
    {
        $carrera = $this->carreraService->getById($id);

        $ciclosDisponibles = $carrera->ciclosLectivos()
            ->orderBy('anio', 'desc')
            ->get();

        $cicloSeleccionado = null;

        if ($request->filled('id_ciclo')) {
            $cicloSeleccionado = $ciclosDisponibles->firstWhere('id_ciclo', (int) $request->id_ciclo);
        }

        if (!$cicloSeleccionado) {
            $cicloSeleccionado = $ciclosDisponibles->firstWhere('estado', 'abierto')
                ?? $ciclosDisponibles->first();
        }

        $materiasPorAnio = $this->materiaService->materiasPorCarreraAgrupadas(
            $carrera,
            $cicloSeleccionado?->id_ciclo
        );

        return view('Secretaria.cursadaSec', compact(
            'carrera', 'materiasPorAnio', 'ciclosDisponibles', 'cicloSeleccionado'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre'        => ['required', 'string', 'max:150', 'regex:/^[\pL\d\s]+$/u', 'unique:carreras,nombre'],
            'titulo'        => ['required', 'string', 'max:150', 'regex:/^[\pL\d\s]+$/u'],
            'id_plan'       => 'nullable|integer|exists:planes_estudio,id_plan',
            'id_resolucion' => 'nullable|integer|exists:resoluciones_carrera,id_resolucion',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras, números y espacios.',
            'titulo.regex' => 'El título solo puede contener letras, números y espacios.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $carrera = $this->carreraService->create($validator->validated());

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Carreras',
            accion: 'crear',
            descripcion: "Creó la carrera \"{$carrera->nombre}\"",
            nombreTabla: 'carreras',
            idRegistro: $carrera->id_carrera
        );

        return response()->json([
            'success' => true,
            'message' => 'Carrera creada correctamente.',
            'data'    => $carrera,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => [
                'required', 'string', 'max:150', 'regex:/^[\pL\d\s]+$/u',
                Rule::unique('carreras', 'nombre')->ignore($id, 'id_carrera'),
            ],
            'titulo'        => ['required', 'string', 'max:150', 'regex:/^[\pL\d\s]+$/u'],
            'id_plan'       => 'nullable|integer|exists:planes_estudio,id_plan',
            'id_resolucion' => 'nullable|integer|exists:resoluciones_carrera,id_resolucion',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras, números y espacios.',
            'titulo.regex' => 'El título solo puede contener letras, números y espacios.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $carrera = $this->carreraService->update($id, $validator->validated());

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Carreras',
            accion: 'actualizar',
            descripcion: "Actualizó la carrera \"{$carrera->nombre}\"",
            nombreTabla: 'carreras',
            idRegistro: $carrera->id_carrera
        );

        return response()->json([
            'success' => true,
            'message' => 'Carrera actualizada correctamente.',
            'data'    => $carrera,
        ]);
    }
   public function planEstudioForm(int $id)
    {
        $carrera = $this->carreraService->getById($id);
        // Ya no hace falta pasar $tiposArchivo como catálogo para elegir,
        // porque ahora se crea uno nuevo en esta pantalla.
        // Si igual querés mostrar los ya creados (ej. para reutilizar), dejalo:
        $tiposArchivo = TipoArchivo::orderBy('descripcion')->get();

        return view('Secretaria.carreraPlanEstudio', compact('carrera', 'tiposArchivo'));
    }

    public function updatePlanEstudio(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nombre' => [
                'required', 'string', 'max:150', 'regex:/^[\pL\d\s]+$/u',
                Rule::unique('carreras', 'nombre')->ignore($id, 'id_carrera'),
            ],
            'titulo'        => ['required', 'string', 'max:150', 'regex:/^[\pL\d\s]+$/u'],
            'id_plan'       => 'required|integer|exists:planes_estudio,id_plan',
            'id_resolucion' => 'required|integer|exists:resoluciones_carrera,id_resolucion',
            'descripcion'   => 'required|string|max:150',
            'total_horas'   => 'required|integer|min:1',
            'fecha'         => 'required|date',
            'archivo'       => 'required|file|mimes:pdf|max:5120',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras, números y espacios.',
            'titulo.regex' => 'El título solo puede contener letras, números y espacios.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $planResolucion = $this->carreraService->completarPlanEstudio(
            $id,
            $validator->safe()->except('archivo'),
            $request->file('archivo')
        );

        $carrera = $this->carreraService->getById($id);

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Carreras',
            accion: 'actualizar',
            descripcion: "Actualizó el plan de estudio de la carrera \"{$carrera->nombre}\"",
            nombreTabla: 'carreras',
            idRegistro: $carrera->id_carrera
        );

        return response()->json([
            'success' => true,
            'message' => 'Plan de estudio actualizado correctamente.',
            'data'    => $planResolucion,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /carreras/{id}/alumnos/agregar
    // Pantalla para matricular alumnos en la carrera (llena estudiante_carrera,
    // que hoy no se completa en ningún otro lado del sistema).
    // -------------------------------------------------------------------------
    public function matricularForm(int $id)
    {
        $carrera = $this->carreraService->getById($id);

        $matriculados = $carrera->estudiantes()->with('usuario.dnis')->get();

        $disponibles = \App\Models\Estudiante::with('usuario.dnis')
            ->whereNotIn('id_usuario', $matriculados->pluck('id_usuario'))
            ->get();

        return view('Secretaria.carreraAlumnosAgregar', compact('carrera', 'disponibles', 'matriculados'));
    }

    // -------------------------------------------------------------------------
    // GET /carreras/{id}/alumnos/disponibles (JSON)
    // Búsqueda en vivo de alumnos todavía no matriculados en la carrera,
    // igual que materias.alumnosDisponibles.
    // -------------------------------------------------------------------------
    public function alumnosDisponibles(Request $request, int $id): JsonResponse
    {
        $carrera = $this->carreraService->getById($id);
        $idsMatriculados = $carrera->estudiantes()->pluck('estudiantes.id_usuario');

        $nombre = $request->get('nombre');

        $disponibles = \App\Models\Estudiante::with('usuario.dnis')
            ->whereNotIn('id_usuario', $idsMatriculados)
            ->when($nombre, function ($query) use ($nombre) {
                $query->whereHas('usuario', function ($q) use ($nombre) {
                    $q->where('nombre', 'like', "%{$nombre}%")
                      ->orWhere('apellido', 'like', "%{$nombre}%");
                });
            })
            ->get()
            ->map(fn ($estudiante) => [
                'id_usuario' => $estudiante->id_usuario,
                'nombre'     => $estudiante->usuario->apellido . ', ' . $estudiante->usuario->nombre,
                'dni'        => $estudiante->usuario->dnis->first()?->dni ?? '—',
            ]);

        return response()->json(['data' => $disponibles]);
    }

    // -------------------------------------------------------------------------
    // POST /carreras/{id}/alumnos
    // Matricula a los alumnos elegidos con cohorte = hoy y estado "Activa".
    // -------------------------------------------------------------------------
    public function matricularStore(Request $request, int $id): JsonResponse
    {
        $carrera = $this->carreraService->getById($id);

        $validator = Validator::make($request->all(), [
            'alumnos'   => 'required|array|min:1',
            'alumnos.*' => 'distinct|integer|exists:estudiantes,id_usuario',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $idEstadoActivo = \App\Models\EstadoCarrera::where('estado', 'Activa')->value('id_estado_carrera')
            ?? \App\Models\EstadoCarrera::value('id_estado_carrera');

        $cohorte = now()->format('Y-m-d');

        foreach ($validator->validated()['alumnos'] as $idAlumno) {
            $carrera->estudiantes()->attach($idAlumno, [
                'cohorte'           => $cohorte,
                'id_estado_carrera' => $idEstadoActivo,
            ]);
        }

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Carreras',
            accion: 'matricular_alumnos',
            descripcion: "Matriculó alumnos en la carrera \"{$carrera->nombre}\"",
            nombreTabla: 'estudiante_carrera',
            idRegistro: $carrera->id_carrera
        );

        return response()->json([
            'success' => true,
            'message' => 'Alumnos matriculados correctamente.',
        ]);
    }
}