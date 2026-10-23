<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Materia;
use App\Models\PlanEstudio;
use App\Services\MateriaService;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MateriaController extends Controller
{
    protected MateriaService $materiaService;
    protected AuditoriaService $auditoriaService;

    public function __construct(MateriaService $materiaService, AuditoriaService $auditoriaService)
    {
        $this->materiaService = $materiaService;
        $this->auditoriaService = $auditoriaService;
    }

    // -------------------------------------------------------------------------
    // GET /carreras/{carrera}/materiaAgregar
    // -------------------------------------------------------------------------
    public function create(Request $request, Carrera $carrera)
    {
        $planesEstudio = PlanEstudio::whereHas('resoluciones', function ($q) use ($carrera) {
            $q->where('plan_resolucion.id_carrera', $carrera->id_carrera);
        })->orderBy('titulo')->get();

        $idCiclo = $request->integer('id_ciclo') ?: null;

        return view('Secretaria.materiaAgregar', compact('carrera', 'planesEstudio', 'idCiclo'));
    }

    // -------------------------------------------------------------------------
    // POST /carreras/{carrera}/materiaAgregar
    // -------------------------------------------------------------------------
    public function store(Request $request, Carrera $carrera): JsonResponse
    {
        $idCiclo = $request->integer('id_ciclo') ?: $this->materiaService->cicloLectivoActivo()?->id_ciclo;
        $validator = Validator::make($request->all(), [
            
            'nombre' => [
                'required', 'string', 'max:150', 'regex:/^[\pL\d\s\-]+$/u',
                Rule::unique('materias', 'nombre')
                    ->where('id_plan', $request->id_plan_estudio)
                    ->where('id_ciclo', $idCiclo),
            ],
            'id_plan_estudio'      => 'required|integer|exists:planes_estudio,id_plan',
            'id_ciclo'             => 'nullable|integer|exists:ciclos_lectivos,id_ciclo',
            'id_anio'              => 'required|integer|min:1|max:5',
            'orden_anio'           => 'required|integer|min:1|max:20',
            'carga_horaria_anual'  => 'required|integer|min:1',
            'acreditable_final'    => 'nullable|boolean',
            'promocionable'        => 'nullable|boolean',

            'archivo'              => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'archivo_descripcion'  => ['nullable', 'string', 'max:150', 'required_with:archivo'],
        ]);

        $validator->after(function ($v) use ($request) {
            $acreditable   = $request->boolean('acreditable_final');
            $promocionable = $request->boolean('promocionable');

            if (!$acreditable && !$promocionable) {
                $mensaje = 'Debe marcar "Acreditable con final" o "Promocionable" (o ambas).';
                $v->errors()->add('acreditable_final', $mensaje);
                $v->errors()->add('promocionable', $mensaje);
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();

        $materia = $this->materiaService->crear($carrera, [
            'nombre'              => $datos['nombre'],
            'id_plan'             => $datos['id_plan_estudio'],
            'id_ciclo'            => $datos['id_ciclo'] ?? null, 
            'anio'                => $datos['id_anio'],
            'orden_anio'          => $datos['orden_anio'],
            'carga_horaria_anual' => $datos['carga_horaria_anual'],
            'acreditable_final'   => $request->boolean('acreditable_final'),
            'promocionable'       => $request->boolean('promocionable'),
            'archivo'             => $request->file('archivo'),
            'archivo_descripcion' => $datos['archivo_descripcion'] ?? null,
        ]);

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Materias',
            accion: 'crear',
            descripcion: "Creó la materia \"{$materia->nombre}\" en la carrera \"{$carrera->nombre}\"",
            nombreTabla: 'materias',
            idRegistro: $materia->id_materia
        );

        return response()->json([
            'success' => true,
            'message' => 'Materia agregada correctamente.',
            'data'    => $materia,
        ], 201);
    }
    // -------------------------------------------------------------------------
    // GET /materiaPerfil/{materia}
    // -------------------------------------------------------------------------
    public function show(int $materia)
    {
        $materia = $this->materiaService->getById($materia);
        $profesoresAsignados = $materia->materiaDocentes;
        $stats = $this->materiaService->estadisticasMateria($materia);


        return view('Secretaria.materiaPerfil', array_merge(
            compact('materia', 'profesoresAsignados'),
            $stats
        ));
    }

    // -------------------------------------------------------------------------
    // PUT /materiaPerfil/{materia}
    // -------------------------------------------------------------------------
    public function update(Request $request, int $materia): JsonResponse
    {
        $materiaModelo = Materia::findOrFail($materia);

        $validator = Validator::make($request->all(), [
            'nombre' => [
                'required', 'string', 'max:150', 'regex:/^[\pL\d\s\-]+$/u',
                Rule::unique('materias', 'nombre')
                    ->where('id_plan', $materiaModelo->id_plan)
                    ->where('id_ciclo', $materiaModelo->id_ciclo)
                    ->ignore($materiaModelo->id_materia, 'id_materia'),
            ],
            'carga_horaria_anual' => 'required|integer|min:1',
            'acreditable_final'   => 'nullable|boolean',
            'promocionable'       => 'nullable|boolean',

            'archivo'             => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'archivo_descripcion' => ['nullable', 'string', 'max:150', 'required_with:archivo'],
        ]);

        $validator->after(function ($v) use ($request) {
            $acreditable   = $request->boolean('acreditable_final');
            $promocionable = $request->boolean('promocionable');

            if (!$acreditable && !$promocionable) {
                $mensaje = 'Debe marcar "Acreditable con final" o "Promocionable" (o ambas).';
                $v->errors()->add('acreditable_final', $mensaje);
                $v->errors()->add('promocionable', $mensaje);
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $datos = $validator->validated();
        $datos['acreditable_final'] = $request->boolean('acreditable_final');
        $datos['promocionable']     = $request->boolean('promocionable');

        // El archivo se maneja aparte: MateriaService::actualizar() solo hace
        // $materia->update($data), así que no puede recibir el UploadedFile.
        $archivo             = $request->file('archivo');
        $archivoDescripcion  = $datos['archivo_descripcion'] ?? null;
        unset($datos['archivo'], $datos['archivo_descripcion']);

        $materiaActualizada = $this->materiaService->actualizar($materia, $datos);

        if ($archivo) {
            $this->materiaService->actualizarArchivo($materiaActualizada, $archivo, $archivoDescripcion);
            $materiaActualizada->refresh();
        }

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Materias',
            accion: 'actualizar',
            descripcion: "Actualizó la materia \"{$materiaActualizada->nombre}\"",
            nombreTabla: 'materias',
            idRegistro: $materiaActualizada->id_materia
        );

        return response()->json([
            'success' => true,
            'message' => 'Materia actualizada correctamente.',
            'data'    => $materiaActualizada,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /materias/{materia}/asignarProfesor
    // -------------------------------------------------------------------------
    public function formAsignarDocente(int $materia)
    {
        $materia   = $this->materiaService->getById($materia);
        $docentes  = $this->materiaService->docentesDisponibles($materia);

        return view('Secretaria.docMatUpload', compact('materia', 'docentes'));
    }

    // -------------------------------------------------------------------------
    // POST /materias/{materia}/asignarProfesor
    // -------------------------------------------------------------------------
    public function storeAsignarDocente(Request $request, int $materia): JsonResponse
    {
        $materiaModelo = Materia::findOrFail($materia);

        $validator = Validator::make($request->all(), [
            'id_docente'        => 'required|integer|exists:docentes,id_usuario',
            'division'          => 'nullable|integer|min:1',
            'situacion_revista' => 'nullable|string|max:50',
            'fecha_inicio' => 'nullable|date|required_with:fecha_fin',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
            'horas_semanales'   => 'nullable|integer|min:1|max:60',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $this->materiaService->asignarDocente($materiaModelo, $validator->validated());

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Materias',
            accion: 'asignar_docente',
            descripcion: "Asignó un docente a la materia \"{$materiaModelo->nombre}\"",
            nombreTabla: 'materia_docente',
            idRegistro: $materiaModelo->id_materia
        );

        return response()->json([
            'success' => true,
            'message' => 'Profesor asignado correctamente.',
        ], 201);
    }

    // -------------------------------------------------------------------------
    // DELETE /materias/{materia}/profesor/{docente}
    // -------------------------------------------------------------------------
    public function destroyDocente(int $materia, int $docente): JsonResponse
    {
        $materiaModelo = Materia::findOrFail($materia);

        $this->materiaService->quitarDocente($materiaModelo, $docente);

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Materias',
            accion: 'quitar_docente',
            descripcion: "Quitó un docente de la materia \"{$materiaModelo->nombre}\"",
            nombreTabla: 'materia_docente',
            idRegistro: $materiaModelo->id_materia
        );

        return response()->json([
            'success' => true,
            'message' => 'Profesor quitado de la materia correctamente.',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /materias/{materia}/cargarNota
    // -------------------------------------------------------------------------
    public function formCargarNotas(int $materia)
    {
        $this->verificarAccesoDocenteOAdmin($materia);

        $materia = $this->materiaService->getById($materia);
        $datos   = $this->materiaService->datosCargarNotas($materia);

        return view('Secretaria.cargarNota', array_merge(['materia' => $materia], $datos));
    }

        // -------------------------------------------------------------------------
    // POST /materias/{materia}/cargarNota
    // -------------------------------------------------------------------------
    public function storeCargarNotas(Request $request, int $materia): JsonResponse
    {
        $this->verificarAccesoDocenteOAdmin($materia);

        $materiaModelo = Materia::findOrFail($materia);

        // -------------------------------------------------------------------
        // Situaciones habilitadas por cuatrimestre según el tipo de materia.
        //
        // El campo que decide si la materia tiene final o no es
        // `acreditable_final` (NO `tfo_edi_udi`, que es solo una
        // clasificación curricular —taller/EDI/UDI obligatorio— y no tiene
        // relación con la modalidad de acreditación).
        //
        // - Sin final (acreditable_final = false): la materia se cierra sí
        //   o sí como promocionada o desaprobada; no existen "aprobado" ni
        //   "promocionado" para este tipo.
        // - Con final + promocionable: puede promocionar sin rendir final,
        //   o quedar aprobada (regular, va a final) o desaprobada.
        // - Con final, no promocionable: solo puede quedar aprobada
        //   (regular) o desaprobada; nunca "promocionado" ni
        //   "promocion_directa".
        // -------------------------------------------------------------------
        if (!$materiaModelo->acreditable_final) {
            $situacionesValidas = ['promocion_directa', 'desaprobado'];
        } elseif ($materiaModelo->promocionable) {
            $situacionesValidas = ['promocionado', 'aprobado', 'desaprobado'];
        } else {
            $situacionesValidas = ['aprobado', 'desaprobado'];
        }

        // El cierre puede quedar en "examen flotante" cuando falta el Rec./Prefinal
        // para decidir; las notas de 1C/2C nunca deberían llegar con ese valor.
        $situacionesValidasCierre = array_merge($situacionesValidas, ['examen_flotante']);

        $validator = Validator::make($request->all(), [
            'alumnos'                      => 'required|array|min:1',
            'alumnos.*.id_usuario' => [ 'required', 'integer',
            Rule::exists('inscripciones_materia', 'id_usuario')->where('id_materia', $materia),
            ],
            'alumnos.*.1C.valor'           => 'nullable|numeric|min:0|max:10',
            'alumnos.*.1C.situacion'       => ['nullable', Rule::in($situacionesValidas)],
            'alumnos.*.2C.valor'           => 'nullable|numeric|min:0|max:10',
            'alumnos.*.2C.situacion'       => ['nullable', Rule::in($situacionesValidas)],
            'alumnos.*.REC.valor'          => 'nullable|numeric|min:0|max:10',
            'alumnos.*.REC.situacion'      => 'nullable|in:aprobado,desaprobado',
            'alumnos.*.CIERRE.valor'       => 'nullable|numeric|min:0|max:10',
            'alumnos.*.CIERRE.situacion'   => ['nullable', Rule::in($situacionesValidasCierre)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $this->materiaService->guardarNotas(
            $materiaModelo,
            $validator->validated()['alumnos']
        );

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Materias',
            accion: 'cargar_notas',
            descripcion: "Cargó notas de cursada en la materia \"{$materiaModelo->nombre}\"",
            nombreTabla: 'notas_cuatrimestre',
            idRegistro: $materiaModelo->id_materia
        );

        return response()->json([
            'success' => true,
            'message' => 'Notas guardadas correctamente.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Chequea que el usuario logueado pueda cargar notas de esta materia:
    // - Administrador: acceso total, sin restricción.
    // - Docente: solo si tiene perfil docente Y esa materia le fue asignada.
    // -------------------------------------------------------------------------
    private function verificarAccesoDocenteOAdmin(int $materia): void
    {
        $usuario = auth()->user();
        $rol = strtolower(trim($usuario?->rol?->descripcion ?? ''));

        if ($rol === 'administrador') {
            return; // admin no tiene restricción por docente
        }

        $docente = $usuario?->docente;

        abort_if(
            !$docente,
            403,
            'El usuario logueado no tiene un perfil de docente asociado.'
        );

        $tieneAcceso = $docente->materias()
            ->where('materias.id_materia', $materia)
            ->exists();

        abort_unless(
            $tieneAcceso,
            403,
            'No tenés acceso a esta materia.'
        );
    }

    // -------------------------------------------------------------------------
    // PATCH /materias/{materia}/alumnos/{alumno}/reprobar
    // -------------------------------------------------------------------------
    public function reprobarAlumno(int $materia, int $alumno): JsonResponse
    {
        $materiaModelo = Materia::findOrFail($materia);

        $this->materiaService->reprobarDirecto($materiaModelo, $alumno);

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Materias',
            accion: 'reprobar_alumno',
            descripcion: "Reprobó directamente a un alumno en la materia \"{$materiaModelo->nombre}\"",
            nombreTabla: 'inscripciones_materia',
            idRegistro: $alumno
        );

        return response()->json([
            'success' => true,
            'message' => 'Alumno reprobado directamente.',
        ]);
    }

    // -------------------------------------------------------------------------
    // PATCH /materias/{materia}/alumnos/{alumno}/restaurar
    // -------------------------------------------------------------------------
    public function restaurarAlumno(int $materia, int $alumno): JsonResponse
    {
        $materiaModelo = Materia::findOrFail($materia);

        $this->materiaService->restaurarCursando($materiaModelo, $alumno);

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Materias',
            accion: 'restaurar_alumno',
            descripcion: "Restauró a un alumno como cursando en la materia \"{$materiaModelo->nombre}\"",
            nombreTabla: 'inscripciones_materia',
            idRegistro: $alumno
        );

        return response()->json([
            'success' => true,
            'message' => 'Alumno restaurado a cursando.',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /materias/{materia}/agregarAlumno
    // -------------------------------------------------------------------------
    public function formAgregarAlumno(Request $request, int $materia)
    {
        $materia = $this->materiaService->getById($materia);

        $busqueda    = $request->get('nombre');
        $disponibles = $this->materiaService->alumnosDisponibles($materia, $busqueda);
        $agregados   = $this->materiaService->alumnosAsignados($materia);

        return view('Secretaria.estMatUpload', compact('materia', 'disponibles', 'agregados'));
    }

    // -------------------------------------------------------------------------
    // GET /materias/{materia}/alumnosDisponibles (AJAX búsqueda)
    // -------------------------------------------------------------------------
    public function buscarAlumnosDisponibles(Request $request, int $materia): JsonResponse
    {
        $materiaModelo = Materia::findOrFail($materia);
        $disponibles   = $this->materiaService->alumnosDisponibles($materiaModelo, $request->get('nombre'));

        return response()->json([
            'success' => true,
            'data'    => $disponibles->map(fn ($e) => [
                'id_usuario' => $e->id_usuario,
                'nombre'     => $e->usuario->apellido . ', ' . $e->usuario->nombre,
                'dni'        => $e->usuario->dnis->first()->dni ?? '—',
            ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /materias/{materia}/agregarAlumno
    // -------------------------------------------------------------------------
    public function storeAgregarAlumno(Request $request, int $materia): JsonResponse
    {
        $materiaModelo = Materia::findOrFail($materia);

        $validator = Validator::make($request->all(), [
            'alumnos'   => 'present|array',
            'alumnos.*' => 'integer|exists:estudiantes,id_usuario',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $this->materiaService->sincronizarAlumnos($materiaModelo, $validator->validated()['alumnos']);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $this->auditoriaService->registrar(
            actor: auth()->user(),
            categoria: 'Materias',
            accion: 'inscribir_alumnos',
            descripcion: "Modificó los alumnos inscriptos en la materia \"{$materiaModelo->nombre}\"",
            nombreTabla: 'inscripciones_materia',
            idRegistro: $materiaModelo->id_materia
        );

        return response()->json([
            'success' => true,
            'message' => 'Alumnos agregados a la materia correctamente.',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /materiaPerfil/{materia}/estudiante
    //
    // El $materia recibido es solo un "ancla" (cualquier fila con ese
    // nombre sirve para identificar de qué materia se trata). La fila que
    // realmente se muestra es la de la ÚLTIMA vez que el alumno logueado se
    // inscribió a una materia con ese nombre (mayor año de ciclo lectivo, y
    // ante empate, la inscripción más reciente). Si viene con
    // ?origen=historial, se respeta la fila puntual que se clickeó, porque
    // ahí el usuario está mirando un registro histórico específico.
    // -------------------------------------------------------------------------
    public function showEstudiante(Request $request, int $materia)
    {
        $materiaModelo = $this->materiaService->getById($materia);

        if ($request->query('origen') !== 'historial') {
            $estudiante = auth()->user()?->estudiante;

            if ($estudiante) {
                $ultima = $this->materiaService->materiaActualParaEstudiante(
                    $estudiante,
                    $materiaModelo->nombre
                );

                if ($ultima) {
                    $materiaModelo = $this->materiaService->getById($ultima->id_materia);
                }
                // Si no hay ninguna inscripción registrada con ese nombre
                // (ej. entró por otra vía), se muestra igual la fila ancla
                // en vez de romper la pantalla.
            }
        }

        $profesoresAsignados = $materiaModelo->materiaDocentes;

        return view('Estudiante.materiaPerfil', [
            'materia'              => $materiaModelo,
            'profesoresAsignados'  => $profesoresAsignados,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /materiaPerfil/{materia}/docente
    //
    // Misma idea que showEstudiante(), pero resuelta contra las
    // asignaciones del docente en materia_docente: se toma la fila de la
    // asignación con fecha_inicio más reciente ("cuando entró" a esa
    // materia por última vez). El control de acceso también se hace sobre
    // el nombre completo (cualquier ciclo en el que haya estado asignado),
    // no solo sobre el id_materia puntual que llegó por URL.
    //
    // Con ?origen=historial se respeta la fila puntual clickeada, igual que
    // en showEstudiante().
    // -------------------------------------------------------------------------
    public function showDocente(Request $request, int $materia)
    {
        $usuario = auth()->user();
        $docente = $usuario?->docente;

        abort_if(
            !$docente,
            403,
            'El usuario logueado no tiene un perfil de docente asociado.'
        );

        $materiaAncla = Materia::findOrFail($materia);
        $esHistorial = $request->query('origen') === 'historial';

        if ($esHistorial) {
            $tieneAcceso = $docente->materias()
                ->where('materias.id_materia', $materia)
                ->exists();

            abort_unless($tieneAcceso, 403, 'No tenés acceso a esta materia.');

            $materiaModelo = $this->materiaService->getById($materia);
        } else {
            $ultima = $this->materiaService->materiaActualParaDocente(
                $docente,
                $materiaAncla->nombre
            );

            abort_unless($ultima, 403, 'No tenés acceso a esta materia.');

            $materiaModelo = $this->materiaService->getById($ultima->id_materia);
        }

        $profesoresAsignados = $materiaModelo->materiaDocentes;

        return view('Docente.materiaPerfil', [
            'materia'             => $materiaModelo,
            'profesoresAsignados' => $profesoresAsignados,
        ]);
    }
}