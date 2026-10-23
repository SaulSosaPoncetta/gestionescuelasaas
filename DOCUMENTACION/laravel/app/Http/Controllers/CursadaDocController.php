<?php 

namespace App\Http\Controllers; 

use App\Models\Docente; 
use Illuminate\Support\Facades\Auth; 
use App\Models\CicloLectivo;
use App\Models\MateriaDocente;

class CursadaDocController extends Controller 
{ 
    /** 
     * Muestra las cursadas del docente logueado, SOLO del ciclo lectivo
     * abierto actual. Usa el mismo criterio que MateriaService::cicloLectivoActivo()
     * (CicloLectivo::abiertos(), basado en fechas) en vez de comparar
     * contra el año calendario: comparar por `anio` fallaba en silencio
     * cuando no había ningún ciclo con ese año exacto, y al no encontrar
     * nada el `when()` no filtraba y se mostraban TODOS los ciclos juntos.
     */ 
    public function index() 
    { 
        $usuario = Auth::user(); 
        $docente = $usuario?->docente; 
        
        abort_if( 
            !$docente, 
            403, 
            'El usuario logueado no tiene un perfil de docente asociado.' 
        ); 
        
        $cicloActual = $this->cicloAbiertoActual();

        // Sin ciclo abierto no hay "cursadas actuales" que mostrar: antes
        // esto silenciosamente mostraba todo, ahora se muestra vacío (la
        // vista ya tiene un estado "No tenés materias asignadas para el
        // ciclo lectivo actual.")
        $cursadas = $cicloActual
            ? MateriaDocente::with([
                    'materia.carrera',
                    'materia.tipoMateria',
                    'cicloLectivo',
                ])
                ->where('id_usuario_docente', $docente->id_usuario)
                ->where('id_ciclo', $cicloActual->id_ciclo)
                ->get()
            : collect();
        
        return view('Docente.cursadaDoc', compact('cursadas')); 
    }
    
    /**
     * Historial: TODAS las cursadas del docente, sin filtrar por ciclo
     * (incluye también el ciclo abierto actual). "Cursadas" ya muestra el
     * recorte del ciclo actual; acá el pedido es ver el listado completo
     * sin excepciones. Antes filtraba por `anio < now()->year`, lo que
     * además dejaba afuera ciclos cerrados del mismo año calendario que el
     * actual.
     */
    public function historial()
    {
        $usuario = Auth::user();
        $docente = $usuario?->docente;

        abort_if(
            !$docente,
            403,
            'El usuario logueado no tiene un perfil de docente asociado.'
        );

        $historial = MateriaDocente::with([
            'materia.carrera',
            'materia.tipoMateria',
            'cicloLectivo',
        ])
        ->where('id_usuario_docente', $docente->id_usuario)
        ->get();

        $carreras = $historial
            ->map(fn ($registro) => $registro->materia->carrera)
            ->filter()
            ->unique('id_carrera')
            ->values();

        return view('Docente.historialDoc', compact(
            'historial',
            'carreras'
        ));
    }

    /**
     * Mismo criterio que MateriaService::cicloLectivoActivo(): el ciclo
     * abierto según sus fechas (scope `abiertos()`), no el que "coincide"
     * con el año calendario actual.
     */
    private function cicloAbiertoActual(): ?CicloLectivo
    {
        return CicloLectivo::abiertos()
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id_ciclo')
            ->first();
    }
    
}