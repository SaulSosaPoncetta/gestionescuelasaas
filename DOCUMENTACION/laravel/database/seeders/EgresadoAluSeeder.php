<?php

namespace Database\Seeders;

use App\Models\CicloLectivo;
use App\Models\EstadoCarrera;
use App\Models\Materia;
use App\Models\MesaExamen;
use App\Models\NotaCuatrimestre;
use App\Models\NotaFinal;
use App\Models\TipoArchivo;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Deja al alumno "alu" (alu@a.com) con el analítico al 100% en la/s carrera/s
 * en las que YA está inscripto (estudiante_carrera), con todas las materias
 * cursadas aprobadas y finales acreditados, y el vínculo marcado como
 * "Egresado" (estados_carrera) con su fecha_egreso, para que aparezca en
 * /egresados (EstudianteService::buscarEgresados).
 *
 * Tiene que correr DESPUÉS de EstudianteCarreraSeeder, MateriasPorCicloSeeder,
 * MesaExamenSeeder, NotaCuatrimestreSeeder y NotaFinalSeeder.
 *
 * Es idempotente: se puede correr más de una vez sin duplicar notas.
 */
class EgresadoAluSeeder extends Seeder
{
    /** Nota con la que se acredita cada materia (>= nota_promocionable = 7). */
    protected float $nota = 8.50;

    public function run(): void
    {
        $alu = Usuario::where('email', 'alu@a.com')->first();

        if (!$alu) {
            $this->command->error('No existe el usuario alu@a.com. Corré UsuarioAdminSeeder primero.');
            return;
        }

        $ciclos = CicloLectivo::orderBy('anio')->get();

        if ($ciclos->isEmpty()) {
            $this->command->error('No hay ciclos lectivos cargados.');
            return;
        }

        // Carreras en las que alu ya está inscripto (la que muestra su analítico).
        $idsCarrera = DB::table('estudiante_carrera')
            ->where('id_usuario', $alu->id_usuario)
            ->pluck('id_carrera');

        // Si no tiene ninguna, se lo inscribe en la primera carrera con materias.
        if ($idsCarrera->isEmpty()) {
            $idCarrera = Materia::query()->value('id_carrera');

            if (!$idCarrera) {
                $this->command->error('No hay materias cargadas. Corré MateriasPorCicloSeeder primero.');
                return;
            }

            $idsCarrera = collect([$idCarrera]);
        }

        foreach ($idsCarrera as $idCarrera) {
            $this->acreditarCarrera($alu, (int) $idCarrera, $ciclos);
        }
    }

    protected function acreditarCarrera(Usuario $alu, int $idCarrera, $ciclos): void
    {
        $materias = Materia::where('id_carrera', $idCarrera)
            ->orderBy('anio')
            ->orderBy('orden_anio')
            ->get();

        if ($materias->isEmpty()) {
            $this->command->warn("La carrera #{$idCarrera} no tiene materias; se omite.");
            return;
        }

        $idTipoArchivo = TipoArchivo::query()->value('id_tipo_archivo'); // puede ser null
        $anioMax       = $materias->max('anio');

        foreach ($materias as $materia) {
            // Año curricular 1 -> primer ciclo, 2 -> segundo, etc. (sin pasarse).
            $ciclo = $ciclos->get(min($materia->anio - 1, $ciclos->count() - 1));

            $fechaCursada = Carbon::create($ciclo->anio, 11, 30);
            $fechaFinal   = Carbon::create($ciclo->anio + 1, 2, 15);

            // Si hay varias cursadas de la misma materia, se pisan todas las del alumno.
            NotaCuatrimestre::where('id_usuario', $alu->id_usuario)
                ->where('id_materia', $materia->id_materia)
                ->delete();

            // periodo 'CIERRE' y situacion 'aprobado' (minúscula): son los valores
            // que leen MateriaService (analítico), CarreraEstController (promedio)
            // y EstudianteService::buscarEgresados().
            NotaCuatrimestre::create([
                'id_usuario'      => $alu->id_usuario,
                'id_materia'      => $materia->id_materia,
                'id_ciclo'        => $ciclo->id_ciclo,
                'valor'           => $this->nota,
                'fecha_carga'     => $fechaCursada,
                'id_tipo_archivo' => $idTipoArchivo,
                'periodo'         => 'CIERRE',
                'situacion'       => 'aprobado',
            ]);

            NotaFinal::where('id_usuario', $alu->id_usuario)
                ->where('id_materia', $materia->id_materia)
                ->delete();

            NotaFinal::create([
                'id_usuario'      => $alu->id_usuario,
                'id_materia'      => $materia->id_materia,
                'id_mesa'         => $this->buscarMesa($materia),
                'valor'           => $this->nota,
                'baja'            => false,
                'id_tipo_archivo' => $idTipoArchivo,
                'fecha_carga'     => $fechaFinal,
            ]);
        }

        // Vínculo alumno-carrera marcado como egresado
        // (DB::table porque EstudianteCarrera no tiene PK simple).
        $primerCiclo = $ciclos->first();
        $ultimoCiclo = $ciclos->get(min($anioMax - 1, $ciclos->count() - 1));

        $valores = [
            'id_estado_carrera' => $this->idEstadoEgresado(),
            'fecha_egreso'      => Carbon::create($ultimoCiclo->anio + 1, 2, 15)->toDateString(),
        ];

        $yaExiste = DB::table('estudiante_carrera')
            ->where('id_usuario', $alu->id_usuario)
            ->where('id_carrera', $idCarrera)
            ->exists();

        // No se toca la cohorte si el vínculo ya existía.
        if (!$yaExiste) {
            $valores['cohorte'] = Carbon::create($primerCiclo->anio, 3, 1)->toDateString();
        }

        DB::table('estudiante_carrera')->updateOrInsert(
            ['id_usuario' => $alu->id_usuario, 'id_carrera' => $idCarrera],
            $valores
        );

        $this->command->info(
            "alu@a.com: {$materias->count()} materias acreditadas (100%) en la carrera #{$idCarrera}."
        );
    }

    /**
     * notas_final.id_mesa es NOT NULL, así que cada final necesita una mesa.
     * Prefiere una mesa de la misma materia; si no hay, usa cualquier mesa.
     */
    protected function buscarMesa(Materia $materia): int
    {
        $idMesa = null;

        try {
            $idMesa = MesaExamen::where('id_materia', $materia->id_materia)->value('id_mesa');
        } catch (\Throwable $e) {
            // La mesa no tiene columna id_materia: se usa el fallback de abajo.
        }

        $idMesa ??= MesaExamen::query()->value('id_mesa');

        if (!$idMesa) {
            throw new \RuntimeException('No hay mesas de examen cargadas. Corré MesaExamenSeeder antes de EgresadoAluSeeder.');
        }

        return $idMesa;
    }

    /**
     * Id del estado "Egresado" en estados_carrera. Mismo criterio que
     * EstudianteService::buscarEgresados() y MateriaService::registrarEgresoSiCorresponde():
     * si no existe, se crea.
     */
    protected function idEstadoEgresado(): int
    {
        return (int) EstadoCarrera::firstOrCreate(['estado' => 'Egresado'])->getKey();
    }
}