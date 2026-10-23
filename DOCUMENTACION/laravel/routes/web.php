<?php

use App\Http\Controllers\LoginPasswordReset;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\PlanEstudioController;
use App\Http\Controllers\CicloLectivoController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\MesaExamenController;
use App\Http\Controllers\EstudianteFinalesController;
use App\Http\Controllers\CarreraEstController;
use App\Http\Controllers\CursadaEstController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\MisComprobantesController;
use App\Http\Controllers\EgresadoController;
use App\Http\Controllers\CursadaDocController;
use App\Http\Controllers\LlamadoController;
use App\Http\Controllers\AyudaController;

// =============================================================================
// Nota: las rutas estáticas (homeSec, materiaUP, alumnoUP, docenteUP,
// dashboard, carreraSec, constanciasSec, analiticoAlumno, planDeEstudio,
// planAlumnos, egresados) vivían acá DUPLICADAS y sin autenticación, antes
// que sus versiones protegidas/reales más abajo. Laravel resuelve la
// primera ruta registrada que matchea método+URI, así que estas pisaban
// a las protegidas y algunas (carreraSec, planDeEstudio) directamente
// dejaban muerto el controller real. Se sacaron de acá; ver el grupo
// 'auth' más abajo.
// =============================================================================

// =============================================================================
// Nota: acá vivía un grupo 'auth' aparte con solo misdatos/perfil.update,
// duplicado de las mismas rutas dentro del grupo 'auth' grande de más abajo
// (incluso con los mismos route names). Se dejó una sola definición, en
// el grupo grande.
// =============================================================================

// =============================================================================
// Nota: acá vivía un bloque de DOCENTES (buscarProfesor, profesorAgregar,
// profesor/{id}, docentes/{id}, usuarios) sin autenticación, duplicado de
// la sección DOCENTES protegida de más abajo. Mismo problema que las
// estáticas: al registrarse primero, le ganaba a la versión con 'auth'.
// Se sacó de acá.
// =============================================================================

// =============================================================================
// AUTH
// =============================================================================

// Redirigir la raíz del dominio al login
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [LoginPasswordReset::class, 'showForm'])
    ->name('password.request');

Route::post('/forgot-password', [LoginPasswordReset::class, 'sendPasswordResetToken'])
    ->name('password.email');

Route::get('/reset-password/{token}', [LoginPasswordReset::class, 'showResetForm'])
    ->name('password.reset');

Route::post('/reset-password', [LoginPasswordReset::class, 'resetPassword'])
    ->name('password.update');

// =============================================================================
// RUTAS PROTEGIDAS (requieren estar autenticado)
// =============================================================================

Route::middleware('auth')->group(function () {

    Route::get('/plan-estudio/espacios/{carrera}', [PlanEstudioController::class, 'espacios'])->name('planEspacios');


    // -------------------------------------------------------------------
    // PERFIL (genérico)
    // -------------------------------------------------------------------

    Route::get('perfil', [ProfileController::class, 'show']);
     // El controller decide internamente qué campos puede editar cada rol.
    Route::put('/perfil', [ProfileController::class, 'update'])->name('perfil.update');

    Route::get('/misComprobantes', [MisComprobantesController::class, 'index'])
        ->name('misComprobantes');
    
    Route::get('/auditoria/{id}/comprobante', [AuditoriaController::class, 'comprobante'])
    ->name('auditoria.comprobante');

    // -------------------------------------------------------------------
    // ESTÁTICAS (vistas sin datos de BD)
    // -------------------------------------------------------------------
Route::middleware(['auth', 'role:administrador'])->group(function () {
    Route::get('/homeSec', function () {
        return view('home');
    })->name('home');

    Route::get('/materiaUP', function () {
        return view('Secretaria/materiasUpload');
    })->name('materiaUP');

    Route::get('/alumnoUP', function () {
        return view('Secretaria/estMatUpload');
    })->name('alumnoUP');

    Route::get('/docenteUP', function () {
        return view('Secretaria/docMatUpload');
    })->name('docenteUP');

    Route::get('/dashboard', fn() => view('home'))->name('dashboard');

    Route::get('/ayuda', [AyudaController::class, 'index'])
    ->name('ayuda');

    Route::get('/agregarPregunta', function () {
        return view('secretaria.preguntaAgregar');
    })->name('preguntaAgregar');

    Route::post('/preguntaAgregar', [AyudaController::class, 'store'])
        ->name('preguntaAgregar.store');

    Route::delete('/ayuda/{ayuda}', [AyudaController::class, 'destroy'])
    ->name('ayuda.destroy');

    Route::get('/constanciasSec', function () {
        return view('Secretaria/constanciasSec');
    });

    Route::get('/egresados', [EgresadoController::class, 'index'])
        ->name('egresados');

    // Perfil propio (mis datos)
    Route::get('Secretaria/misdatos', [ProfileController::class, 'show'])->name('misdatos');


    // -------------------------------------------------------------------
    // DOCENTES
    // -------------------------------------------------------------------

    Route::get('/buscarProfesor', [DocenteController::class, 'index'])
        ->name('profesoresSec');

    Route::get('/profesorAgregar', [DocenteController::class, 'create'])
        ->name('profesoresAgregar');

    Route::post('/profesorAgregar', [DocenteController::class, 'store'])
        ->name('profesores.store');

    Route::put('/profesor/{id}', [DocenteController::class, 'update'])
        ->name('profesores.update');

    Route::get('/docentes/{id}', [DocenteController::class, 'show'])
        ->name('docentes.show');

    Route::patch('/docentes/{id}/estado', [DocenteController::class, 'toggleEstado'])
        ->name('docentes.toggleEstado');

    Route::get('/usuarios', function () {
        return view('Secretaria.usuarios');
    });

    // -------------------------------------------------------------------
    // ALUMNOS
    // -------------------------------------------------------------------

    Route::get('/buscarAlumno', [EstudianteController::class, 'index'])
        ->name('alumnos.index');

    Route::get('/alumnoAgregar', [EstudianteController::class, 'create'])
        ->name('alumnoAgregar');

    Route::post('/alumnoAgregar', [EstudianteController::class, 'store'])
        ->name('alumnos.store');

    Route::get('/alumnoPerfil/{id}', [EstudianteController::class, 'show'])
        ->name('alumnos.show');

    Route::put('/alumnoPerfil/{id}', [EstudianteController::class, 'update'])
        ->name('alumnos.update');

    Route::patch('/alumnos/{id}/estado', [EstudianteController::class, 'toggleEstado'])
        ->name('alumnos.toggleEstado');


    Route::get('/alumnoAnalitico/{id}', [EstudianteController::class, 'analitico'])
    ->name('alumnos.analitico');




    // =====================================================
    // ADMINISTRADORES
    // =====================================================



    Route::get('/buscarAdministrador', [AdministradorController::class, 'index'])
        ->name('administracionSec');

    Route::get('/administradorAgregar', [AdministradorController::class, 'create'])
        ->name('administradores.create');

    Route::post('/administradorAgregar', [AdministradorController::class, 'store'])
        ->name('administradores.store');

    Route::put('/administrador/{id}', [AdministradorController::class, 'update'])
        ->name('administradores.update');

    Route::get('/administradorPerfil/{id}', [AdministradorController::class, 'show'])
        ->name('administradores.show');

    Route::patch('/administradores/{id}/estado', [AdministradorController::class, 'toggleEstado']);



    // -------------------------------------------------------------------
    // CARRERAS
    // -------------------------------------------------------------------

    Route::get('/carreraSec', [CarreraController::class, 'index'])
        ->name('carreras.index');

    Route::get('/carreraAgregar', [CarreraController::class, 'create'])
        ->name('carreras.create');

    Route::post('/carreraAgregar', [CarreraController::class, 'store'])
        ->name('carreras.store');

    Route::get('/carreraPerfil/{id}', [CarreraController::class, 'show'])
        ->name('carreras.show');

    Route::get('/carreras/{id}/editar', [CarreraController::class, 'edit'])->name('carreras.edit');

    Route::put('/carrera/{id}', [CarreraController::class, 'update'])
        ->name('carreras.update');

    Route::get('/carreras/{id}/plan-estudio', [CarreraController::class, 'planEstudioForm'])
        ->name('carreras.planEstudio.form');

    Route::put('/carreras/{id}/plan-estudio', [CarreraController::class, 'updatePlanEstudio'])
        ->name('carreras.planEstudio.update');

    Route::get('/carreras/{id}/alumnos/agregar', [CarreraController::class, 'matricularForm'])
        ->name('carreras.alumnos.form');

    Route::get('/carreras/{id}/alumnos/disponibles', [CarreraController::class, 'alumnosDisponibles'])
        ->name('carreras.alumnos.disponibles');

    Route::post('/carreras/{id}/alumnos', [CarreraController::class, 'matricularStore'])
        ->name('carreras.alumnos.store');

    // -------------------------------------------------------------------
    // CICLO LECTIVO
    // -------------------------------------------------------------------

    Route::get('/cicloLectivo', [CicloLectivoController::class, 'index'])
    ->name('cicloLectivoSec');

    Route::get('/cicloLectivoAgregar', [CicloLectivoController::class, 'create'])
    ->name('cicloLectivoAgregar');

    Route::post('/cicloLectivo', [CicloLectivoController::class, 'store'])
    ->name('cicloLectivo.store');

    Route::get('/cicloLectivo/{id}', [CicloLectivoController::class, 'show'])
    ->name('cicloLectivoPerfil');

    Route::put('/cicloLectivo/{id}', [CicloLectivoController::class, 'update'])
    ->name('cicloLectivo.update');

    Route::patch('/cicloLectivo/{id}/toggle-estado', [CicloLectivoController::class, 'toggleEstado'])
    ->name('cicloLectivo.toggleEstado');

    // -------------------------------------------------------------------
    // CURSADA / MATERIAS
    // -------------------------------------------------------------------

    Route::get('/carreras/{carrera}/materiaAgregar', [MateriaController::class, 'create'])
        ->name('materias.create');

    Route::post('/carreras/{carrera}/materiaAgregar', [MateriaController::class, 'store'])
        ->name('materias.store');

    Route::get('/materiaPerfil/{materia}', [MateriaController::class, 'show'])
        ->name('materias.show');

    Route::put('/materiaPerfil/{materia}', [MateriaController::class, 'update'])
        ->name('materias.update');

    Route::get('/materias/{materia}/asignarProfesor', [MateriaController::class, 'formAsignarDocente'])
        ->name('materias.asignarProfesor.form');

    Route::post('/materias/{materia}/asignarProfesor', [MateriaController::class, 'storeAsignarDocente'])
        ->name('materias.asignarProfesor.store');

    Route::delete('/materias/{materia}/profesor/{docente}', [MateriaController::class, 'destroyDocente'])
        ->name('materias.profesor.destroy');



    Route::patch('/materias/{materia}/alumnos/{alumno}/restaurar', [MateriaController::class, 'restaurarAlumno'])
        ->name('materias.alumnos.restaurar');

    Route::get('/materias/{materia}/agregarAlumno', [MateriaController::class, 'formAgregarAlumno'])
        ->name('materias.agregarAlumno.form');

    Route::get('/materias/{materia}/alumnosDisponibles', [MateriaController::class, 'buscarAlumnosDisponibles'])
        ->name('materias.alumnosDisponibles');

    Route::post('/materias/{materia}/agregarAlumno', [MateriaController::class, 'storeAgregarAlumno'])
        ->name('materias.agregarAlumno.store');


    // -------------------------------------------------------------------
    // PLAN DE ESTUDIO
    // -------------------------------------------------------------------

    Route::get('/planDeEstudio', [PlanEstudioController::class, 'index'])
        ->name('planDeEstudio.index');

    Route::post('/planDeEstudio', [PlanEstudioController::class, 'store'])
        ->name('planDeEstudio.store');

    Route::get('/plan-estudio', [PlanEstudioController::class, 'index'])->name('planEstudio.index');
    Route::get('/plan-estudio/agregar', [PlanEstudioController::class, 'create'])->name('planEstudio.create');
    Route::post('/plan-estudio', [PlanEstudioController::class, 'store'])->name('planEstudio.store');
    Route::get('/plan-estudio/{id}/editar', [PlanEstudioController::class, 'edit'])->name('planEstudio.edit');
    Route::put('/plan-estudio/{id}', [PlanEstudioController::class, 'update'])->name('planEstudio.update');
    Route::get('/plan-estudio/{id}/perfil', [PlanEstudioController::class, 'perfil'])
    ->name('planEstudio.perfil');

    Route::get('/plan-estudio/{carrera}/alumnos', [PlanEstudioController::class, 'alumnos'])
        ->name('planAlumnos');

    Route::get('/plan-estudio/{carrera}/alumnos', [PlanEstudioController::class, 'alumnos'])->name('planAlumnos');

    // -------------------------------------------------------------------
    // AUDITORIA
    // -------------------------------------------------------------------

    Route::get('/auditoria', [AuditoriaController::class, 'index'])
        ->name('auditoria');

    

    
    // -------------------------------------------------------------------
    // FINALES
    // -------------------------------------------------------------------

    Route::get('/finalesSec', [MesaExamenController::class, 'finalesView'])
        ->name('finalesSec');

    

    Route::get('/CargarNotasFinal/{id}', [MesaExamenController::class, 'cargarNotasView'])
        ->name('cargarNotasFinal');

    Route::get('/finalesAgregarFecha', [MesaExamenController::class, 'agregarFechaForm'])
        ->name('finalesAgregarFecha');

    Route::post('/finalesAgregarFecha', [MesaExamenController::class, 'store'])
        ->name('finales.store');

    Route::get('/finales/{id}/agregarEstudiante', [MesaExamenController::class, 'agregarEstudianteForm'])
        ->name('finalesAgregarEst');

    Route::post('/finales/{id}/estudiantes', [MesaExamenController::class, 'addEstudiantes'])
        ->name('finales.estudiantes.store');

    Route::get('/finalesHistorial', [MesaExamenController::class, 'historialView'])
        ->name('finalesHistorial');

    Route::put('/mesaFinalPerfil/{id}', [MesaExamenController::class, 'updateNotas'])
        ->name('mesaFinalPerfil.update');

    Route::post('/cargarNotaFinal/{id}', [MesaExamenController::class, 'cargarNotas'])
        ->name('cargarNotaFinal.store');

    Route::get('/finalesHistorial/data', [MesaExamenController::class, 'historial'])
        ->name('finalesHistorial.data');

    Route::get('/finales', [MesaExamenController::class, 'index'])
        ->name('finales.index');

    Route::get('/mesaFinalPerfil/{id}', [MesaExamenController::class, 'show'])
        ->name('mesaFinalPerfil.show');

    Route::put('/finales/{id}', [MesaExamenController::class, 'update'])
        ->name('finales.update');

    Route::get('/llamados', [LlamadoController::class, 'index'])->name('llamados.index');
    Route::post('/llamados', [LlamadoController::class, 'store'])->name('llamados.store');

    // -------------------------------------------------------------------
    // COMPROBANTE
    // -------------------------------------------------------------------
    // La ruta real con datos vive en AuditoriaController::comprobante()
    // (ver sección AUDITORIA), que arma un $comprobante y renderiza esta
    // misma vista genérica reusable ('comprobante').
});
Route::middleware(['auth', 'role:estudiante'])->group(function () {

    Route::get('/homeEst', function () {
        return view('home');
    })->name('homeEst');

    Route::get('/Carrera', [CarreraEstController::class, 'index'])
        ->name('carreraEst');

    Route::get('/cursadaEst', [CursadaEstController::class, 'index'])
    ->name('cursadaEst');

    Route::get('/matriculacion', [CursadaEstController::class, 'matriculacion'])->name('matriculacion');
    Route::post('/matriculacion', [CursadaEstController::class, 'matricularStore'])->name('matriculacion.store');



    Route::get('/finalesEst', [EstudianteFinalesController::class, 'index'])
        ->name('finales');

    Route::get('/finalesInscripcion', [EstudianteFinalesController::class, 'inscripcionView'])
        ->name('finalesInscripcion');

    Route::post('/finalesInscripcion', [EstudianteFinalesController::class, 'inscribir'])
        ->name('finalesInscripcion.store');

    Route::post('/finalesEst/baja', [EstudianteFinalesController::class, 'baja'])
        ->name('finalesEst.baja');
    
    Route::get('/finalPerfilAlu/{id}', [MesaExamenController::class, 'finalPerfilView'])
    ->name('finalPerfilAlu');

    Route::get('/plan-estudio/{id}', [PlanEstudioController::class, 'show'])
        ->name('planEstudio.show');

    Route::get('/materiaPerfil/{materia}/estudiante', [MateriaController::class, 'showEstudiante'])
        ->name('materias.show.estudiante');

    Route::get('/Estudiante/misDatos', [ProfileController::class, 'showEstudiante'])
        ->name('misdatos.estudiante');

    Route::get('/misAnalitico', [EstudianteController::class, 'analiticoPropio'])
    ->name('alumnos.analitico.propio');

});
    // =====================================================
    // Profesores
    // =====================================================

    Route::middleware(['auth', 'role:docente'])->group(function () {

    Route::get('/homeDoc', function () {
        return view('home');
    })->name('homeDoc');

    Route::get('/misDatos', [ProfileController::class, 'showDocente'])
    ->name('misdatos.Docente');

    Route::get('/Cursadas', [CursadaDocController::class, 'index'])
    ->name('cursadaDoc');

    Route::get('/finalesDoc', [MesaExamenController::class, 'finalesDocView'])
    ->name('finalesDoc');

    Route::get('/finalesHistorialDoc', [MesaExamenController::class, 'finalesHistorialDocView'])
    ->name('finalesHistorialDoc');

    Route::get('/materiaPerfil/{materia}/docente', [MateriaController::class, 'showDocente'])
    ->name('materias.show.docente');

    Route::get('/historial', [CursadaDocController::class, 'historial'])
    ->name('historialDoc');

    Route::get('/alumnosHistorial', function () {
        return view('Docente.alumnosHistorial');
    })->name('alumnosHistorial');

});


Route::middleware(['auth', 'role:administrador, docente'])->group(function () {
    Route::get('/materias/{materia}/cargarNota', [MateriaController::class, 'formCargarNotas'])
    ->name('materias.cargarNota.form');

    Route::post('/materias/{materia}/cargarNota', [MateriaController::class, 'storeCargarNotas'])
        ->name('materias.cargarNota.store');

    Route::patch('/materias/{materia}/alumnos/{alumno}/reprobar', [MateriaController::class, 'reprobarAlumno'])
        ->name('materias.alumnos.reprobar');
    
    Route::get('/finalPerfil/{id}', [MesaExamenController::class, 'showView'])
        ->name('finalPerfil');
});
});
