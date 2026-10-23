<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Services\CursadaEstService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CursadaEstController extends Controller
{
    public function __construct(
        protected CursadaEstService $cursadaEstService
    ) {}

    /**
     * Muestra la pantalla "Cursada" del estudiante logueado.
     */
    public function index(Request $request)
    {
        $estudiante = $this->estudianteLogueado();

        $data = $this->cursadaEstService->obtenerDatosCursada($estudiante);

        return view('Estudiante.cursadaEst', $data);
    }

    /**
     * Muestra la pantalla de matriculación a materias del estudiante logueado.
     */
    public function matriculacion(Request $request)
    {
        $estudiante = $this->estudianteLogueado();

        $data = $this->cursadaEstService->obtenerDatosMatriculacion($estudiante);

        return view('Estudiante.matriculacion', $data);
    }

    /**
     * Guarda la matriculación (AJAX): recibe los ids de materia que el
     * estudiante movió a la tabla "Alumnos a agregar" y las inscribe.
     */
    public function matricularStore(Request $request): JsonResponse
    {
        $estudiante = $this->estudianteLogueado();

        try {
            $validated = $request->validate([
                'materias'   => ['required', 'array', 'min:1'],
                'materias.*' => ['integer', 'distinct'],
            ]);

            $creadas = $this->cursadaEstService->matricular($estudiante, $validated['materias']);

            return response()->json([
                'ok'       => true,
                'mensaje'  => 'Matriculación guardada correctamente.',
                'cantidad' => $creadas->count(),
            ]);
        } catch (ValidationException $e) {
            // Errores esperados: validación del request o reglas de negocio
            // (correlativas, ciclo cerrado, ya matriculado, etc).
            return response()->json([
                'ok'      => false,
                'mensaje' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Throwable $e) {
            // Cualquier otra cosa (SQL, null pointer, etc): la logueamos con el
            // detalle real y al front le devolvemos algo genérico pero, al
            // menos, en NUESTRO formato {ok, mensaje} para que el JS no rompa.
            Log::error('Error al guardar matriculación', [
                'id_usuario' => $estudiante->id_usuario,
                'materias'   => $request->input('materias'),
                'exception'  => $e,
            ]);

            return response()->json([
                'ok'      => false,
                'mensaje' => 'Ocurrió un error al guardar la matriculación. Intentá de nuevo o contactá a la secretaría.',
            ], 500);
        }
    }

    protected function estudianteLogueado(): Estudiante
    {
        $usuario = Auth::user();
        $estudiante = $usuario?->estudiante;

        abort_if(!$estudiante, 403, 'El usuario logueado no tiene un perfil de estudiante asociado.');

        return $estudiante;
    }
}