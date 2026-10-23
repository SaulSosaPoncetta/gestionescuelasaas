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

    // -------------------------------------------------------------------
    // ESTÁTICAS (vistas sin datos de BD)
    // -------------------------------------------------------------------

    Route::get('/homeSec', function () {
        return view('Secretaria.homeSec');
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

    Route::get('/dashboard', fn() => view('homeSec'))->name('dashboard');

    // Nota: el stub estático de /carreraSec se sacó de acá: tapaba a la
    // ruta real (CarreraController::index) más abajo, en la sección CARRERAS.

    Route::get('/constanciasSec', function () {
        return view('Secretaria/constanciasSec');
    });

    Route::get('/analiticoAlumno', function () {
        return view('Secretaria/analitico');
    });

    

    Route::get('/egresados', function () {
        return view('Secretaria/egresados');
    })->name('egresados');

    // Perfil propio (mis datos)
    Route::get('Secretaria/misdatos', [ProfileController::class, 'show'])->name('misdatos');
    Route::put('/Secretaria/perfil', [ProfileController::class, 'update'])->name('perfil.update');

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

    // -------------------------------------------------------------------
    // ADMINISTRADORES
    // -------------------------------------------------------------------

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
    // PERFIL (genérico)
    // -------------------------------------------------------------------

    Route::get('perfil', [ProfileController::class, 'show']);
    Route::put('perfil', [ProfileController::class, 'update']);

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

    Route::get('/materias/{materia}/cargarNota', [MateriaController::class, 'formCargarNotas'])
        ->name('materias.cargarNota.form');

    Route::post('/materias/{materia}/cargarNota', [MateriaController::class, 'storeCargarNotas'])
        ->name('materias.cargarNota.store');

    Route::patch('/materias/{materia}/alumnos/{alumno}/reprobar', [MateriaController::class, 'reprobarAlumno'])
        ->name('materias.alumnos.reprobar');

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

    Route::get('/plan-estudio/espacios/{carrera}', [PlanEstudioController::class, 'espacios'])->name('planEspacios');

    Route::get('/plan-estudio/{carrera}/alumnos', [PlanEstudioController::class, 'alumnos'])
        ->name('planAlumnos');

    // -------------------------------------------------------------------
    // AUDITORIA
    // -------------------------------------------------------------------

    Route::get('/auditoria', function () {
        return view('Secretaria/auditoria');
    })->name('auditoria');

    // -------------------------------------------------------------------
    // FINALES
    // -------------------------------------------------------------------

    Route::get('/finalesSec', [MesaExamenController::class, 'finalesView'])
        ->name('finalesSec');

    Route::get('/finalPerfil/{id}', [MesaExamenController::class, 'showView'])
        ->name('finalPerfil');

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

    // -------------------------------------------------------------------
    // COMPROBANTE
    // -------------------------------------------------------------------

    Route::get('/comprobante', function () {
        return view('comprobante');
    })->name('comprobante');


    // -------------------------------------------------------------------
    // ESTUDIANTES
    // -------------------------------------------------------------------

    Route::get('/homeEst', function () {
        return view('Estudiante.homeEst');
    })->name('homeEst');

    Route::get('/Carrera', [CarreraEstController::class, 'index'])
    ->name('carreraEst');

    Route::get('/finalesEst', [EstudianteFinalesController::class, 'index'])
    ->name('finales');

    Route::get('/finalesInscripcion', [EstudianteFinalesController::class, 'inscripcionView'])
        ->name('finalesInscripcion');

    Route::post('/finalesInscripcion', [EstudianteFinalesController::class, 'inscribir'])
        ->name('finalesInscripcion.store');

    Route::post('/finalesEst/baja', [EstudianteFinalesController::class, 'baja'])
    ->name('finalesEst.baja');
    
    Route::get('/plan-estudio/{id}', [PlanEstudioController::class, 'show'])->name('planEstudio.show');

    Route::get('/materiaPerfil/{materia}/estudiante', [MateriaController::class, 'showEstudiante'])->name('materias.show.estudiante');

    Route::get('Estudiante/misDatos', [ProfileController::class, 'showEstudiante'])->name('misdatos.estudiante');
});





