<?php

namespace App\Http\Controllers;

use App\Models\Llamado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LlamadoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Llamado::orderBy('id_llamado')->get(['id_llamado', 'descripcion', 'meses_validos'])
        );
    }

    /**
     * POST /llamados
     *
     * 'meses_validos' es siempre obligatorio acá: la única excepción libre
     * de mes es "Extracurricular", que ya viene cargada por el seeder y no
     * se crea desde este formulario.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'descripcion'     => ['required', 'string', 'max:100', 'unique:llamados,descripcion'],
            'meses_validos'   => ['required', 'array', 'min:1'],
            'meses_validos.*' => ['integer', 'min:1', 'max:12'],
        ]);

        $llamado = Llamado::create($data);

        return response()->json($llamado, 201);
    }
}