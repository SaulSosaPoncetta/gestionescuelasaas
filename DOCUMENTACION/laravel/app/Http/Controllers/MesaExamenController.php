<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\CicloLectivo;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Llamado;
use App\Models\Materia;
use App\Models\MesaExamen;
use App\Models\TipoMesaExamen;
use App\Services\MesaExamenService;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MesaExamenController extends Controller
{
    protected AuditoriaService $auditoriaService;

    public function __construct(protected MesaExamenService $mesaExamenService, AuditoriaService $auditoriaService)
    {
        $this->auditoriaService = $auditoriaService;
    }

    /**
     * GET /finalesSec  (name: finalesSec)
     * Vista "Fechas de finales". Filtros por querystring (recarga la
     * página completa); usa MesaExamenService::getAll() igual que la
     * versión JSON (index()), pero renderizado en Blade.
     */
    public function finalesView(Request $request): View
    {
        // Excluye del combo cualquier carrera "placeholder"/sin asignar.
        // Ajustar el nombre exacto si en tu BD es distinto, o cambiar por
        // un where sobre una columna de tipo/flag si existe.
        $carreras = Carrera::where('nombre', '!=', 'Sin asignar')
            ->orderBy('nombre')
            ->get(['id_carrera', 'nombre']);

        $anios = [1, 2, 3, 4, 5];

        $filters = $request->only(['id_carrera', 'anio']);

        $cicloActual = $this->mesaExamenService->cicloActual();
        $filters['id_ciclo'] = $cicloActual->id_ciclo ?? null;
        $filters['solo_vigentes'] = true;

        $mesasPaginadas = $this->mesaExamenService->getAll($filters, 100);

        $mesas = $mesasPaginadas->getCollection()->map(function (MesaExamen $mesa) {
            return (object) [
                'id_mesa'    => $mesa->id_mesa,
                'fecha_hora' => optional($mesa->fecha)->format('d/m/Y H:i') . ' hs',
                'espacio'    => $mesa->materia->nombre ?? '',
                'carrera'    => $mesa->materia->carrera->nombre ?? '',
                'anio'       => $mesa->materia->anio ?? null,
                'profesores' => $mesa->docentes
                    ->map(fn ($d) => $d->usuario->apellido ?? '')
                    ->filter()
                    ->implode(', '),
            ];
        });

        return view('Secretaria.finales', compact('carreras', 'anios', 'mesas', 'filters'));
    }

    /**
     * GET /finalesHistorial  (name: finalesHistorial)
     * Vista "Historial de finales" con datos reales.
     */
    public function historialView(): View
    {
        $carreras = Carrera::where('nombre', '!=', 'Sin asignar')
            ->orderBy('nombre')
            ->get(['id_carrera', 'nombre']);

        $llamados = Llamado::orderBy('id_llamado')
            ->get(['id_llamado', 'descripcion'])
            ->map(fn ($l) => (object) ['id_llamado' => $l->id_llamado, 'nombre' => $l->descripcion]);

        $anios = [1, 2, 3, 4, 5];

        $mesas = MesaExamen::with(['materia', 'docentes.usuario'])
            ->where('fecha', '<=', now()->subDay()->endOfDay())   // <-- nuevo
            ->orderBy('fecha')
            ->get()
            ->map(function (MesaExamen $mesa) {
                return (object) [
                    'id_mesa'    => $mesa->id_mesa,
                    'id_carrera' => $mesa->materia->id_carrera ?? null,
                    'id_llamado' => $mesa->id_llamado,
                    'anio'       => $mesa->materia->anio ?? null,
                    'fecha_hora' => optional($mesa->fecha)->format('d/m/Y H:i') . ' hs',
                    'espacio'    => $mesa->materia->nombre ?? '',
                    'profesores' => $mesa->docentes
                        ->map(fn ($d) => $d->usuario->apellido ?? '')
                        ->filter()
                        ->implode(', '),
                ];
            });

        return view('Secretaria.finalesHistorial', compact('carreras', 'llamados', 'anios', 'mesas'));
    }

    /**
     * GET /finalPerfil/{id}  (name: finalPerfil)
     * Vista "Mesa de final" con datos reales.
     *
     * El Blade edita id_materia / fecha / presidente / vocal / aula
     * inline, así que además de $mesa y $alumnosFinal necesita los
     * combos completos de materias y docentes.
     */
    public function showView(int $id): View
    {
        $mesaModel = $this->mesaExamenService->find($id);

        abort_if(!$mesaModel, 404, 'Mesa de examen no encontrada');

        $materias = Materia::orderBy('nombre')->get(['id_materia', 'nombre']);

        $docentes = Docente::with('usuario')
            ->get()
            ->map(fn ($d) => (object) [
                'id_usuario' => $d->id_usuario,
                'nombre'     => trim(($d->usuario->apellido ?? '') . ', ' . ($d->usuario->nombre ?? ''), ', '),
            ])
            ->values();

        $mesa = (object) [
            'id_mesa'       => $mesaModel->id_mesa,
            'id_materia'    => $mesaModel->id_materia,
            'fecha_input'   => optional($mesaModel->fecha)->format('Y-m-d'),
            'hora_input'    => optional($mesaModel->fecha)->format('H:i'),
            'aula'          => $mesaModel->aula ?? '',
            'id_presidente' => optional($mesaModel->presidente)->id_usuario,
            'id_vocal'      => optional($mesaModel->vocales->first())->id_usuario,
            'vocales'       => $mesaModel->vocales
                ->map(fn ($v) => $v->usuario->apellido ?? '')
                ->filter()
                ->values()
                ->all(),
            'archivo'       => $mesaModel->archivo,
        ];

        $alumnosFinal = $mesaModel->estudiantes->map(function ($estudiante) {
            return (object) [
                'id_usuario' => $estudiante->id_usuario,
                'nombre'     => trim(($estudiante->usuario->apellido ?? '') . ', ' . ($estudiante->usuario->nombre ?? ''), ', '),
                'nota'       => $estudiante->nota,
                'cant'       => $estudiante->cant,
            ];
        });

        return view('Secretaria.finalPerfil', compact('mesa', 'alumnosFinal', 'materias', 'docentes'));
    }

    /**
     * GET /CargarNotasFinal/{id}  (name: cargarNotasFinal)
     * Vista "Cargar notas final" con datos reales.
     */
    public function cargarNotasView(int $id): View
    {
        $mesaModel = $this->mesaExamenService->find($id);

        abort_if(!$mesaModel, 404, 'Mesa de examen no encontrada');

        $mesa = (object) [
            'id_mesa' => $mesaModel->id_mesa,
            'fecha'   => optional($mesaModel->fecha)->format('d/m/Y'),
            'hora'    => optional($mesaModel->fecha)->format('H:i') . ' hs',
            'materia' => $mesaModel->materia->nombre ?? '',
            'anio'    => ($mesaModel->materia->anio ?? '') . '°',
        ];

        $alumnosFinal = $mesaModel->estudiantes->map(function ($estudiante) {
            return (object) [
                'id_usuario' => $estudiante->id_usuario,
                'nombre'     => trim(($estudiante->usuario->apellido ?? '') . ', ' . ($estudiante->usuario->nombre ?? ''), ', '),
                'cant'       => $estudiante->cant,
            ];
        });

        return view('Secretaria.cargarNotasFinal', compact('mesa', 'alumnosFinal'));
    }

    /**
     * GET /finalesAgregarFecha  (name: finalesAgregarFecha)
     * Vista "Agregar Final". El submit va a store() (POST /finalesAgregarFecha).
     */
    public function agregarFechaForm(): View
    {
        $carreras = Carrera::orderBy('nombre')->get(['id_carrera', 'nombre']);

        $materias = Materia::orderBy('nombre')->get(['id_materia', 'nombre', 'id_carrera']);

        $tiposMesa = TipoMesaExamen::orderBy('id_tipo_mesa')->get(['id_tipo_mesa', 'descripcion']);

        $llamados = Llamado::orderByRaw("descripcion = 'Extracurricular' DESC")
        ->orderBy('id_llamado')
        ->get(['id_llamado', 'descripcion']);

        $ciclos = CicloLectivo::orderByDesc('anio')->get(['id_ciclo', 'anio']);

        $docentes = Docente::with('usuario')
            ->get()
            ->map(fn ($d) => (object) [
                'id_usuario' => $d->id_usuario,
                'nombre'     => trim(($d->usuario->apellido ?? '') . ', ' . ($d->usuario->nombre ?? ''), ', '),
            ])
            ->values();

        return view('Secretaria.finalesAgregarFecha', compact(
            'carreras',
            'materias',
            'tiposMesa',
            'llamados',
            'ciclos',
            'docentes'
        ));
    }

    /**
     * GET /finales/{id}/agregarEstudiante  (name: finalesAgregarEst)
     * Vista "Agregar Alumno a final". El submit va a addEstudiantes()
     * (POST /finales/{id}/estudiantes). Solo se listan alumnos que
     * todavía NO están cargados en esta mesa.
     */
    public function agregarEstudianteForm(int $id): View
    {
        $mesaModel = $this->mesaExamenService->find($id);

        abort_if(!$mesaModel, 404, 'Mesa de examen no encontrada');

        $mesa = (object) [
            'id_mesa' => $mesaModel->id_mesa,
            'materia' => $mesaModel->materia->nombre ?? '',
            'fecha'   => optional($mesaModel->fecha)->format('d/m/Y'),
        ];

        $idsYaAgregados = $mesaModel->estudiantes->pluck('id_usuario');

        $estudiantesDisponibles = Estudiante::with('usuario')
            ->whereNotIn('id_usuario', $idsYaAgregados)
            ->get()
            ->map(fn ($e) => (object) [
                'id_usuario' => $e->id_usuario,
                'nombre'     => trim(($e->usuario->apellido ?? '') . ', ' . ($e->usuario->nombre ?? ''), ', '),
                'documento'  => $e->usuario->documento ?? '',
            ])
            ->values();

return view('Secretaria.finalesAgregarEst', compact('mesa', 'estudiantesDisponibles'));    }

    /**
     * GET /finales
     * Pantalla "Fechas de finales" (con estado calculado por mesa), JSON.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'id_ciclo',
            'id_tipo_mesa',
            'id_llamado',
            'id_materia',
            'id_carrera',
            'anio',
            'fecha_desde',
            'fecha_hasta',
            'aula_desde',
            'aula_hasta',
            'estudiantes',
        ]);

        $perPage = (int) $request->get('per_page', 15);

        $mesas = $this->mesaExamenService->getAll($filters, $perPage);

        return response()->json($mesas);
    }

    /**
     * GET /finalesHistorial/data (JSON, si algún día el historial pasa a AJAX)
     */
    public function historial(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'id_carrera'  => ['required', 'integer', 'exists:carreras,id_carrera'],
            'id_llamado'  => ['required', 'integer', 'exists:llamados,id_llamado'],
            'anio'        => ['nullable', 'integer'],
        ]);

        $mesas = $this->mesaExamenService->getHistorial($filters);

        return response()->json($mesas);
    }

    /**
     * GET /mesaFinalPerfil/{id_mesa} (JSON)
     */
    public function show(int $id): JsonResponse
    {
        $mesa = $this->mesaExamenService->find($id);

        if (!$mesa) {
            return response()->json(['message' => 'Mesa de examen no encontrada'], 404);
        }

        return response()->json($mesa);
    }

    /**
     * POST /finales
     * Pantalla "Agregar Final". Crea 1 o 2 mesas según las fechas cargadas.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
        'id_materia'                 => ['required', 'integer', 'exists:materias,id_materia'],
        'id_tipo_mesa'               => ['required', 'integer', 'exists:tipos_mesa_examen,id_tipo_mesa'],
        'id_llamado'                 => ['nullable', 'integer', 'exists:llamados,id_llamado'],
        'id_ciclo'                   => ['required', 'integer', 'exists:ciclos_lectivos,id_ciclo'],
        'aula'                       => ['nullable', 'string', 'max:255'],
        'id_usuario_administracion'  => ['required', 'integer', 'exists:administradores,id_usuario'],
        'habilitado'                 => ['sometimes', 'boolean'],

        'docente_titular'            => ['required', 'integer', 'exists:docentes,id_usuario'],
        'docente_vocal'              => ['nullable', 'integer', 'exists:docentes,id_usuario'],

        'fecha_1_desde'              => ['required', 'date'],
        'fecha_2_desde'              => ['nullable', 'date'],

        'archivo'                    => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        'archivo_descripcion'        => ['nullable', 'string', 'max:150', 'required_with:archivo'],
    ]);

        $fechas = array_filter([
            $data['fecha_1_desde'],
            $data['fecha_2_desde'] ?? null,
        ]);

        unset($data['fecha_1_desde'], $data['fecha_2_desde']);
        $data['fechas'] = array_values($fechas);

        try {
            $mesas = $this->mesaExamenService->create($data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $materiaNombre = Materia::find($data['id_materia'])->nombre ?? 'materia desconocida';

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Finales',
            accion: 'crear',
            descripcion: "Creó mesa/s de examen para \"{$materiaNombre}\"",
            nombreTabla: 'mesas_examen'
        );

        return response()->json($mesas, 201);
    }

    /**
     * PUT/PATCH /finales/{id}
     * Edición administrativa de la mesa. Distinto del botón "Modificar" de
     * "Mesa de final", que solo edita notas (ver updateNotas más abajo).
     */
  public function update(Request $request, int $id): JsonResponse
{
    $mesa = $this->mesaExamenService->find($id);

    if (!$mesa) {
        return response()->json(['message' => 'Mesa de examen no encontrada'], 404);
    }

    $data = $request->validate([
        'id_materia'      => ['sometimes', 'integer', 'exists:materias,id_materia'],
        'fecha'           => ['sometimes', 'date'],
        'aula'            => ['nullable', 'string', 'max:255'],
        'docente_titular' => ['nullable', 'integer', 'exists:docentes,id_usuario'],
        'docente_vocal'   => ['nullable', 'integer', 'exists:docentes,id_usuario'],

        'archivo'             => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        'archivo_descripcion' => ['nullable', 'string', 'max:150', 'required_with:archivo'],
    ]);

    if ($request->hasFile('archivo')) {
        $this->mesaExamenService->actualizarArchivo(
            $mesa,
            $request->file('archivo'),
            $data['archivo_descripcion'] ?? null,
            auth()->id()
        );
    }
    unset($data['archivo'], $data['archivo_descripcion']);

    $mesaActualizada = $this->mesaExamenService->update($mesa, $data);

    $this->auditoriaService->registrar(
        actor: auth()->user(),
        categoria: 'Finales',
        accion: 'actualizar',
        descripcion: "Actualizó la mesa de examen \"{$mesaActualizada->materia->nombre}\"",
        nombreTabla: 'mesas_examen',
        idRegistro: $mesaActualizada->id_mesa
    );

    return response()->json([
        'success' => true,
        'message' => 'Mesa actualizada correctamente.',
        'data'    => $mesaActualizada,
    ]);
}

    /**
     * POST /finales/{id}/estudiantes
     * Pantalla "Agregar Alumno a final".
     */
    public function addEstudiantes(Request $request, int $id): JsonResponse
    {
        $mesa = $this->mesaExamenService->find($id);

        if (!$mesa) {
            return response()->json(['message' => 'Mesa de examen no encontrada'], 404);
        }

        $data = $request->validate([
            'estudiantes'    => ['required', 'array', 'min:1'],
            'estudiantes.*'  => ['integer', 'exists:estudiantes,id_usuario'],
        ]);

        $mesa = $this->mesaExamenService->addEstudiantes($mesa, $data['estudiantes']);

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Finales',
            accion: 'agregar_alumnos',
            descripcion: "Agregó alumnos a la mesa de examen \"{$mesa->materia->nombre}\"",
            nombreTabla: 'mesas_examen',
            idRegistro: $mesa->id_mesa
        );

        return response()->json($mesa);
    }

    /**
     * POST /cargarNotaFinal/{id_mesa}
     * Pantalla "Cargar notas final" (carga inicial dedicada).
     */
    public function cargarNotas(Request $request, int $id): JsonResponse
    {
        return $this->guardarNotas($request, $id);
    }

    /**
     * PUT /mesaFinalPerfil/{id_mesa}
     * Botón "Modificar" dentro de "Mesa de final".
     */
    public function updateNotas(Request $request, int $id): JsonResponse
    {
        return $this->guardarNotas($request, $id);
    }

    /**
     * Compartido por cargarNotas() y updateNotas(). El form manda
     * name="nota_{id_usuario}" (entero 1-10). 'cant' nunca se envía: se
     * calcula solo, no es editable.
     */
    protected function guardarNotas(Request $request, int $id): JsonResponse
    {
        $mesa = $this->mesaExamenService->find($id);

        if (!$mesa) {
            return response()->json(['success' => false, 'message' => 'Mesa de examen no encontrada'], 404);
        }

        $rules = [];
        foreach ($mesa->estudiantes as $estudiante) {
            $rules['nota_' . $estudiante->id_usuario] = ['nullable', 'numeric', 'min:1', 'max:10'];
        }

        $data = $request->validate($rules);

        $notas = [];
        foreach ($data as $campo => $valor) {
            if (!preg_match('/^nota_(\d+)$/', $campo, $m)) {
                continue;
            }
            $notas[(int) $m[1]] = $valor;
        }

        $this->mesaExamenService->cargarNotas($mesa, $notas);

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Finales',
            accion: 'cargar_notas',
            descripcion: "Cargó notas de final en la mesa \"{$mesa->materia->nombre}\"",
            nombreTabla: 'notas_final',
            idRegistro: $mesa->id_mesa
        );

        return response()->json([
            'success' => true,
            'message' => 'Notas guardadas correctamente.',
        ]);
    }
   /**
     * GET /finalesDoc  (name: finalesDoc)
     * Vista "Fechas de finales" para el rol Docente: solo mesas VIGENTES
     * (estado 'ABIERTA', calculado por MesaExamenService::appendEstado) donde
     * el docente logueado participa (como titular o vocal). Las mesas
     * cerradas se ven en finalesHistorialDoc, no acá.
     */
    public function finalesDocView(Request $request): View
    {
        $carreras = Carrera::orderBy('nombre')->get(['id_carrera', 'nombre']);

        $anios = [1, 2, 3, 4, 5];

        $idDocente = auth()->id();

        $filters = $request->only(['id_carrera', 'anio']);
        $filters['id_docente'] = $idDocente;

        $mesasPaginadas = $this->mesaExamenService->getAll($filters, 100);

        $mesas = $mesasPaginadas->getCollection()
            ->filter(fn (MesaExamen $mesa) => $mesa->estado === 'ABIERTA')
            ->map(function (MesaExamen $mesa) use ($idDocente) {
                $miParticipacion = $mesa->docentes->firstWhere('id_usuario', $idDocente);

                return (object) [
                    'id_mesa'    => $mesa->id_mesa,
                    'fecha_hora' => optional($mesa->fecha)->format('d/m/Y H:i') . ' hs',
                    'espacio'    => $mesa->materia->nombre ?? '',
                    'carrera'    => $mesa->materia->carrera->nombre ?? '',
                    'anio'       => $mesa->materia->anio ?? null,
                    'situacion'  => $miParticipacion?->pivot->cargo === 'titular' ? 'Titular' : 'Vocal',
                    'profesores' => $mesa->docentes
                        ->map(fn ($d) => $d->usuario->apellido ?? '')
                        ->filter()
                        ->implode(', '),
                ];
            })
            ->values();

        return view('Docente.finalesDoc', compact('carreras', 'anios', 'mesas', 'filters'));
    }

    /**
     * GET /finalesHistorialDoc  (name: finalesHistorialDoc)
     * Vista "Historial de finales" para el rol Docente: solo mesas CERRADAS
     * (estado != 'ABIERTA', calculado por MesaExamenService::appendEstado) donde
     * el docente logueado participó (como titular o vocal).
     */
    public function finalesHistorialDocView(Request $request): View
    {
        $carreras = Carrera::orderBy('nombre')->get(['id_carrera', 'nombre']);

        $anios = [1, 2, 3, 4, 5];

        $idDocente = auth()->id();

        $filters = $request->only(['id_carrera', 'anio']);
        $filters['id_docente'] = $idDocente;

        $mesasPaginadas = $this->mesaExamenService->getAll($filters, 100);

        $mesas = $mesasPaginadas->getCollection()
            ->filter(fn (MesaExamen $mesa) => $mesa->estado !== 'ABIERTA')
            ->map(function (MesaExamen $mesa) use ($idDocente) {
                $miParticipacion = $mesa->docentes->firstWhere('id_usuario', $idDocente);

                return (object) [
                    'id_mesa'    => $mesa->id_mesa,
                    'fecha_hora' => optional($mesa->fecha)->format('d/m/Y H:i') . ' hs',
                    'espacio'    => $mesa->materia->nombre ?? '',
                    'carrera'    => $mesa->materia->carrera->nombre ?? '',
                    'anio'       => $mesa->materia->anio ?? null,
                    'situacion'  => $miParticipacion?->pivot->cargo === 'titular' ? 'Titular' : 'Vocal',
                    'profesores' => $mesa->docentes
                        ->map(fn ($d) => $d->usuario->apellido ?? '')
                        ->filter()
                        ->implode(', '),
                ];
            })
            ->values();

        return view('Docente.finalesHistorial', compact('carreras', 'anios', 'mesas', 'filters'));
    }
    
    // GET /finalPerfilAlu/{id_mesa}  (name: finalPerfilAlu)
    public function finalPerfilView(int $id): View
    {
        $mesa = app(MesaExamenService::class)->find($id);

        abort_if(!$mesa, 404, 'Mesa de examen no encontrada');

        // Chequeo de pertenencia: el alumno solo puede ver mesas a las que
        // está o estuvo inscripto, no cualquier id_mesa por URL.
        $idUsuario = auth()->user()->id_usuario;
        $tieneAcceso = $mesa->estudiantes->contains('id_usuario', $idUsuario);
        abort_if(!$tieneAcceso, 403);

        return view('Estudiante.finalesPerfilAlu', compact('mesa'));
    }
}