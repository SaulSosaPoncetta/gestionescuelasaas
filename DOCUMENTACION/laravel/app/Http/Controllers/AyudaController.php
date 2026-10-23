<?php

namespace App\Http\Controllers;

use App\Models\Ayuda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AyudaController extends Controller
{
    public function index()
    {
        $usuario = Auth::user();

        $rol = $usuario->rol;

        $ayudas = Ayuda::with('roles')
            ->whereHas('roles', function ($query) use ($rol) {
                $query->where('roles.id_rol', $rol->id_rol);
            })
            ->get();

        return view('ayuda', compact('ayudas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string'],
            'video_url' => ['nullable', 'url'],
            'rol_administrador' => ['nullable', 'boolean'],
            'rol_docente' => ['nullable', 'boolean'],
            'rol_estudiante' => ['nullable', 'boolean'],
        ]);

        $roles = [];

        if ($request->boolean('rol_administrador')) {
            $roles[] = 1;
        }

        if ($request->boolean('rol_docente')) {
            $roles[] = 2;
        }

        if ($request->boolean('rol_estudiante')) {
            $roles[] = 3;
        }

        if (empty($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Tenés que seleccionar al menos un rol.',
                'errors' => [
                    'roles' => [
                        'Tenés que seleccionar al menos un rol.'
                    ]
                ]
            ], 422);
        }

        DB::beginTransaction();

        try {
            $ayuda = Ayuda::create([
                'titulo_ayuda' => $request->titulo,
                'descripcion' => $request->descripcion,
                'video_url' => $request->video_url,
            ]);

            $ayuda->roles()->attach($roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'La pregunta se agregó correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'No se pudo guardar la pregunta.',
            ], 500);
        }
    }

    public function destroy(Ayuda $ayuda)
    {
        try {
            $ayuda->delete();

            return response()->json([
                'success' => true,
                'message' => 'La pregunta se eliminó correctamente.',
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar la pregunta.',
            ], 500);
        }
    }
}