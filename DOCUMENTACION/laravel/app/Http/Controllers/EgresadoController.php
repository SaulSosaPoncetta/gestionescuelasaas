<?php

namespace App\Http\Controllers;

use App\Services\EstudianteService;
use App\Models\Carrera;
use Illuminate\Http\Request;

class EgresadoController extends Controller
{
    protected EstudianteService $estudianteService;

    public function __construct(EstudianteService $estudianteService)
    {
        $this->estudianteService = $estudianteService;
    }

    // -------------------------------------------------------------------------
    // GET /egresados
    // Listado de egresados (alumnos que acreditaron el 100% de su carrera),
    // con filtro opcional por carrera, año de egreso y cohorte.
    // -------------------------------------------------------------------------
    public function index(Request $request)
    {
        $idCarrera = $request->get('carrera');
        $anio      = $request->get('anio');
        $cohorte   = $request->get('cohorte');

        $egresados = $this->estudianteService->buscarEgresados($idCarrera, $anio, $cohorte);
        $opciones  = $this->estudianteService->opcionesFiltroEgresados();

        $carreras = Carrera::orderBy('nombre')->get();

        return view('Secretaria.egresados', [
            'egresados' => $egresados,
            'carreras'  => $carreras,
            'anios'     => $opciones['anios'],
            'cohortes'  => $opciones['cohortes'],
            'idCarrera' => $idCarrera,
            'anio'      => $anio,
            'cohorte'   => $cohorte,
        ]);
    }
}
