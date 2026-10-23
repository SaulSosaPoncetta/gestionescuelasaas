<?php

namespace App\Http\Controllers;
use App\Models\CicloLectivo;
use App\Models\Carrera;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreCicloLectivoRequest;
use App\Http\Requests\UpdateCicloLectivoRequest;

class CicloLectivoController extends Controller
{
    protected AuditoriaService $auditoriaService;

    public function __construct(AuditoriaService $auditoriaService)
    {
        $this->auditoriaService = $auditoriaService;
    }

    public function index()
    {
        $ciclos = CicloLectivo::orderBy('fecha_inicio', 'desc')->get();

        return view('Secretaria.cicloLectivoSec', compact('ciclos'));
    }

    public function show(string $id)
    {
        $ciclo = CicloLectivo::with('carreras.planVigente.plan', 'carreras.planVigente.resolucion')
            ->findOrFail($id);

        $carrerasDisponibles = Carrera::with(['planVigente.plan', 'planVigente.resolucion'])
            ->where('nombre', '!=', Carrera::NOMBRE_SIN_ASIGNAR)
            ->orderBy('nombre')
            ->get();

        $idsAsignados = $ciclo->carreras->pluck('id_carrera')->toArray();

        return view('Secretaria.cicloLectivoPerfil', compact('ciclo', 'carrerasDisponibles', 'idsAsignados'));
    }

    public function create()
    {
        $carreras = Carrera::with(['planVigente.plan', 'planVigente.resolucion'])
            ->where('nombre', '!=', Carrera::NOMBRE_SIN_ASIGNAR)
            ->orderBy('nombre')
            ->get();

        return view('Secretaria.cicloLectivoAgregar', compact('carreras'));
    }

    public function store(StoreCicloLectivoRequest $request)
    {
        $ciclo = CicloLectivo::create([
            'anio' => $request->anio,
            'descripcion' => "Ciclo Lectivo " . $request->anio,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'inicio_matriculacion' => $request->inicio_matriculacion,
            'fin_matriculacion' => $request->fin_matriculacion,
            'estado' => 'abierto',
        ]);

        // Carreras asignadas al ciclo (opcional: puede crearse sin ninguna)
        $ciclo->carreras()->sync($request->input('carreras', []));

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Ciclos lectivos',
            accion: 'crear',
            descripcion: "Creó el ciclo lectivo {$ciclo->anio}",
            nombreTabla: 'ciclos_lectivos',
            idRegistro: $ciclo->id_ciclo
        );

        return response()->json([
            'success' => true,
            'message' => 'Ciclo lectivo agregado correctamente.',
            'data'    => $ciclo,
        ], 201);
    }

    public function update(UpdateCicloLectivoRequest $request, string $id)
    {
        $ciclo = CicloLectivo::findOrFail($id);

        $ciclo->update([
            'anio' => $request->anio,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'inicio_matriculacion' => $request->inicio_matriculacion,
            'fin_matriculacion' => $request->fin_matriculacion,
            'estado' => $request->estado ?? $ciclo->estado,
        ]);

        if ($request->has('carreras')) {
            $ciclo->carreras()->sync($request->input('carreras', []));
        }

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Ciclos lectivos',
            accion: 'actualizar',
            descripcion: "Actualizó el ciclo lectivo {$ciclo->anio}",
            nombreTabla: 'ciclos_lectivos',
            idRegistro: $ciclo->id_ciclo
        );

        return response()->json([
            'success' => true,
            'message' => 'Ciclo lectivo actualizado correctamente.',
            'data' => $ciclo]);
    }

    public function toggleEstado(string $id)
    {
        $ciclo = CicloLectivo::findOrFail($id);

        $nuevoEstado = $ciclo->estaAbierto() ? 'cerrado' : 'abierto';
        $ciclo->update(['estado' => $nuevoEstado]);

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Ciclos lectivos',
            accion: 'actualizar',
            descripcion: "Cambió el estado del ciclo lectivo {$ciclo->anio} a {$nuevoEstado}",
            nombreTabla: 'ciclos_lectivos',
            idRegistro: $ciclo->id_ciclo
        );

        return response()->json([
            'success' => true,
            'message' => "Ciclo lectivo marcado como {$nuevoEstado}.",
            'data' => $ciclo]);
    }
}