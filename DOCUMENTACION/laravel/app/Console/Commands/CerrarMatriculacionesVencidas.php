<?php

namespace App\Console\Commands;

use App\Models\CicloLectivo;
use App\Services\CursadaEstService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CerrarMatriculacionesVencidas extends Command
{
    protected $signature = 'matriculacion:cerrar-vencidas';

    protected $description = 'Pasa a "Libre" las materias disponibles que los estudiantes no matricularon antes de que venciera fin_matriculacion.';

    /**
     * SUPUESTO: reviso ciclos cuyo fin_matriculacion venció en los últimos
     * 7 días. Es una ventana arbitraria para no reprocesar TODOS los ciclos
     * históricos cada vez que corre el comando (que igual sería inofensivo
     * por la idempotencia de cerrarMatriculacionCiclo(), pero sí lento).
     * Ajustar si necesitás procesar ciclos más viejos (por ejemplo si el
     * comando estuvo caído más de una semana).
     */
    protected const DIAS_HACIA_ATRAS = 7;

    public function handle(CursadaEstService $service): int
    {
        $ciclos = CicloLectivo::query()
            ->whereDate('fin_matriculacion', '<', now())
            ->whereDate('fin_matriculacion', '>=', now()->subDays(self::DIAS_HACIA_ATRAS))
            ->get();

        if ($ciclos->isEmpty()) {
            $this->info('No hay ciclos con matriculación recién vencida para procesar.');
            return self::SUCCESS;
        }

        foreach ($ciclos as $ciclo) {
            try {
                $cantidad = $service->cerrarMatriculacionCiclo($ciclo);

                $this->info("Ciclo {$ciclo->id_ciclo} ({$ciclo->descripcion}): {$cantidad} materia(s) pasada(s) a Libre.");
            } catch (\Throwable $e) {
                Log::error('Error cerrando matriculación de ciclo', [
                    'id_ciclo'  => $ciclo->id_ciclo,
                    'exception' => $e,
                ]);

                $this->error("Falló el cierre del ciclo {$ciclo->id_ciclo}, ver storage/logs/laravel.log.");
            }
        }

        return self::SUCCESS;
    }
}