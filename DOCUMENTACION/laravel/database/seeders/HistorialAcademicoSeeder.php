<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\CicloLectivo;
use App\Models\EstadoEstudiante;
use App\Models\Estudiante;
use App\Models\InscripcionMateria;
use App\Models\Llamado;
use App\Models\Materia;
use App\Models\MesaExamen;
use App\Models\NotaCuatrimestre;
use App\Models\NotaFinal;
use App\Models\TipoEstudiante;
use App\Models\TipoMesaExamen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistorialAcademicoSeeder extends Seeder
{
    /**
     * [id_usuario => [nombreMateria => true]]. Se va llenando a medida que
     * recorremos los ciclos en orden cronológico: si un alumno desaprueba
     * (o queda reprobado directo) una materia, queda "pendiente" y el
     * siguiente ciclo lo prioriza para re-inscribirlo en la materia con el
     * mismo nombre (que es otra fila de Materia, la de ese ciclo).
     */
    protected array $pendientesPorEstudiante = [];

    public function run(): void
    {
        $tipoRegular  = TipoEstudiante::firstOrCreate(['descripcion_tipo' => 'Regular']);
        $estadoActivo = EstadoEstudiante::firstOrCreate(['descripcion_estado' => 'Activo']);
        $tipoMesa     = TipoMesaExamen::first();
        $llamado      = Llamado::first();

        $ciclos = CicloLectivo::orderBy('anio')->get();

        if ($ciclos->isEmpty()) {
            $this->command->warn('No hay ciclos lectivos cargados.');
            return;
        }

        // Con 1 solo ciclo no hay "historial" posible: todo lo que se genere
        // va a caer en ese único ciclo (abierto o no). Avisamos para que no
        // sorprenda ver el historial vacío.
        if ($ciclos->count() < 2) {
            $this->command->warn(
                'Solo hay 1 ciclo lectivo cargado. Para tener historial real '
                . '(alumnos con notas de ciclos anteriores) necesitás varios '
                . 'CicloLectivo con años pasados, y correr MateriasPorCicloSeeder '
                . 'para cada uno de ellos antes de este seeder.'
            );
        }

        $estudiantes = Estudiante::all();

        if ($estudiantes->isEmpty()) {
            $this->command->warn('No hay estudiantes cargados.');
            return;
        }

        $stats = [
            'inscripciones'      => 0,
            'notasCuatrimestre'  => 0,
            'notasFinal'         => 0,
            'ciclosSinMaterias'  => 0,
            'estudiantesSinCarreraEnCiclo' => 0,
        ];

        foreach ($ciclos as $ciclo) {

            $esAbierto = $ciclo->estaAbierto();

            $materiasPorCarrera = Materia::where('id_ciclo', $ciclo->id_ciclo)
                ->get()
                ->groupBy('id_carrera');

            if ($materiasPorCarrera->isEmpty()) {
                // Antes esto era un `continue` silencioso. Es la causa más
                // probable de que el historial aparezca vacío: si este
                // ciclo no tiene materias generadas (MateriasPorCicloSeeder
                // no corrió para él), no se genera nada acá.
                $this->command->warn(
                    "Ciclo {$ciclo->anio} (id {$ciclo->id_ciclo}) no tiene materias "
                    . "generadas. Corré MateriasPorCicloSeeder para este ciclo primero. Saltando."
                );
                $stats['ciclosSinMaterias']++;
                continue;
            }

            foreach ($estudiantes as $estudiante) {
                $this->procesarEstudianteEnCiclo(
                    $estudiante,
                    $ciclo,
                    $esAbierto,
                    $materiasPorCarrera,
                    $tipoRegular,
                    $estadoActivo,
                    $tipoMesa,
                    $llamado,
                    $stats
                );
            }
        }

        $this->command->info(
            "Listo: {$stats['inscripciones']} inscripciones, "
            . "{$stats['notasCuatrimestre']} notas de cuatrimestre y "
            . "{$stats['notasFinal']} notas de final generadas."
        );

        if ($stats['ciclosSinMaterias'] > 0) {
            $this->command->warn("{$stats['ciclosSinMaterias']} ciclo(s) se saltearon por no tener materias.");
        }
        if ($stats['estudiantesSinCarreraEnCiclo'] > 0) {
            $this->command->warn(
                "{$stats['estudiantesSinCarreraEnCiclo']} combinación(es) alumno+ciclo se saltearon "
                . "porque la carrera del alumno no tenía materias generadas en ese ciclo."
            );
        }
    }

    private function procesarEstudianteEnCiclo(
        Estudiante $estudiante,
        CicloLectivo $ciclo,
        bool $esAbierto,
        $materiasPorCarrera,
        TipoEstudiante $tipoRegular,
        EstadoEstudiante $estadoActivo,
        ?TipoMesaExamen $tipoMesa,
        ?Llamado $llamado,
        array &$stats
    ): void {
        $idCarrera = DB::table('estudiante_carrera')
            ->where('id_usuario', $estudiante->id_usuario)
            ->value('id_carrera') ?? $materiasPorCarrera->keys()->random();

        $materiasCarrera = $materiasPorCarrera->get($idCarrera);

        if (!$materiasCarrera || $materiasCarrera->isEmpty()) {
            $stats['estudiantesSinCarreraEnCiclo']++;
            return;
        }

        $materiasElegidas = $this->elegirMaterias($estudiante, $materiasCarrera);

        foreach ($materiasElegidas as $materia) {

            $inscripcion = InscripcionMateria::firstOrCreate([
                'id_usuario' => $estudiante->id_usuario,
                'id_materia' => $materia->id_materia,
                'id_ciclo'   => $ciclo->id_ciclo,
            ], [
                'fecha_inscripcion'    => $this->fechaAleatoriaEntre($ciclo->inicio_matriculacion, $ciclo->fin_matriculacion),
                'id_tipo_estudiante'   => $tipoRegular->id_tipo_estudiante,
                'id_estado_estudiante' => $estadoActivo->id_estado_estudiante,
                'contador_finales'     => rand(0, 3),
                'fecha_vencimiento'    => $this->fechaAleatoriaEntre('now', (new \DateTime('now'))->modify('+2 years')),
                'libro'                => 'L-' . rand(1, 20),
                'folio'                => 'F-' . rand(1, 500),
                'division'             => rand(1, 5),
            ]);

            if ($inscripcion->wasRecentlyCreated) {
                $stats['inscripciones']++;
            }

            // Ciclos cerrados: siempre se cierra la cursada. En el ciclo
            // abierto, el 60% ya tiene cierre y el resto sigue cursando.
            $debeCerrar = !$esAbierto || fake()->boolean(60);

            $situacionCierre = $this->generarNotasCuatrimestre(
                $materia, $estudiante, $ciclo, $debeCerrar, $stats
            );

            if ($situacionCierre === null) {
                continue; // sigue cursando, todavía no hay nada que resolver
            }

            $aprobada = in_array($situacionCierre, ['promocionado', 'promocion_directa', 'aprobado'], true);

            if ($aprobada) {
                unset($this->pendientesPorEstudiante[$estudiante->id_usuario][$materia->nombre]);
            } else {
                $this->pendientesPorEstudiante[$estudiante->id_usuario][$materia->nombre] = true;
            }

            // Cerró "aprobado" (regular, queda pendiente el final) y la
            // materia tiene final: simulamos también la mesa y la nota.
            if ($situacionCierre === 'aprobado' && $materia->acreditable_final) {
                $this->generarNotaFinal($materia, $estudiante, $ciclo, $tipoMesa, $llamado, $stats);
            }
        }
    }

    /**
     * Prioriza las materias que el alumno tiene pendientes de un ciclo
     * anterior (mismo nombre, distinta fila por pertenecer a este ciclo) y
     * completa el resto al azar hasta 3-6 materias.
     */
    private function elegirMaterias(Estudiante $estudiante, $materiasCarrera)
    {
        $cantidad = rand(3, min(6, $materiasCarrera->count()));

        $nombresPendientes = array_keys($this->pendientesPorEstudiante[$estudiante->id_usuario] ?? []);

        $pendientes = $nombresPendientes
            ? $materiasCarrera->whereIn('nombre', $nombresPendientes)->values()
            : collect();

        $restantes = $materiasCarrera->diff($pendientes);
        $faltan = max(0, $cantidad - $pendientes->count());

        $completar = $restantes->isNotEmpty()
            ? $restantes->random(min($faltan, $restantes->count()))
            : collect();

        return $pendientes->merge($completar);
    }

    /**
     * Situaciones válidas por cuatrimestre según el tipo de materia, igual
     * al criterio de MateriaController::storeCargarNotas.
     */
    private function situacionesValidas(Materia $materia): array
    {
        if (!$materia->acreditable_final) {
            return ['promocion_directa', 'desaprobado'];
        }

        if ($materia->promocionable) {
            return ['promocionado', 'aprobado', 'desaprobado'];
        }

        return ['aprobado', 'desaprobado'];
    }

    /**
     * Genera las notas de cuatrimestre. Devuelve la situación de cierre
     * (string) si cerró, o null si el alumno sigue cursando (solo carga 1C).
     *
     * El cierre YA NO es un tercer valor random independiente: se calcula
     * a partir de 1C/2C (y REC si hace falta) con la misma lógica que
     * calcularCierreFila() en cargarNota.js, para que los datos de prueba
     * sean consistentes con lo que haría un docente real desde la UI.
     */
    private function generarNotasCuatrimestre(
        Materia $materia,
        Estudiante $estudiante,
        CicloLectivo $ciclo,
        bool $debeCerrar,
        array &$stats
    ): ?string {
        if (!$debeCerrar) {
            NotaCuatrimestre::updateOrCreate([
                'id_materia' => $materia->id_materia,
                'id_usuario' => $estudiante->id_usuario,
                'id_ciclo'   => $ciclo->id_ciclo,
                'periodo'    => '1C',
            ], [
                'valor'       => fake()->randomFloat(2, 4, 10),
                'situacion'   => fake()->randomElement(['aprobado', 'desaprobado']),
                'fecha_carga' => $this->fechaAleatoriaEntre($ciclo->fecha_inicio, new \DateTime('now')),
            ]);
            $stats['notasCuatrimestre']++;

            return null;
        }

        // "reprobado_directo" es una acción administrativa (ver
        // MateriaController::reprobarAlumno), no un resultado de promediar
        // 1C/2C. La mantenemos como salida temprana con su propia
        // probabilidad, igual que antes, pero separada del cálculo de
        // cierre normal.
        if (fake()->boolean(10)) {
            NotaCuatrimestre::updateOrCreate([
                'id_materia' => $materia->id_materia,
                'id_usuario' => $estudiante->id_usuario,
                'id_ciclo'   => $ciclo->id_ciclo,
                'periodo'    => 'CIERRE',
            ], [
                'valor'       => 0,
                'situacion'   => 'reprobado_directo',
                'fecha_carga' => $this->fechaAleatoriaEntre($ciclo->fecha_inicio, $ciclo->fecha_fin),
            ]);
            $stats['notasCuatrimestre']++;

            return 'reprobado_directo';
        }

        $situacionesValidas = $this->situacionesValidas($materia);
        $esPromocionDirecta = !$materia->acreditable_final;

        // 1C y 2C se generan como cargas independientes (como haría un
        // docente cuatrimestre a cuatrimestre), cada una con su propio
        // valor numérico.
        [$s1, $v1] = $this->generarNotaCuatrimestreAleatoria($situacionesValidas);
        [$s2, $v2] = $this->generarNotaCuatrimestreAleatoria($situacionesValidas);

        foreach ([['1C', $s1, $v1], ['2C', $s2, $v2]] as [$periodo, $situacion, $valor]) {
            NotaCuatrimestre::updateOrCreate([
                'id_materia' => $materia->id_materia,
                'id_usuario' => $estudiante->id_usuario,
                'id_ciclo'   => $ciclo->id_ciclo,
                'periodo'    => $periodo,
            ], [
                'valor'       => $valor,
                'situacion'   => $situacion,
                'fecha_carga' => $this->fechaAleatoriaEntre($ciclo->fecha_inicio, $ciclo->fecha_fin),
            ]);
            $stats['notasCuatrimestre']++;
        }

        $cierre = $this->calcularCierre($s1, $v1, $s2, $v2, null, null, $esPromocionDirecta);

        // Si 1C y 2C fueron distintas y una fue "desaprobado", el cierre
        // queda pendiente de un recuperatorio (examen_flotante) hasta que
        // se carga REC. Generamos ese REC acá para que el dato quede
        // cerrado, tal como pasaría en la vida real cuando el docente
        // termina de cargar todo.
        $sRec = null;
        $vRec = null;

        if ($cierre['situacion'] === 'examen_flotante') {
            $notaAprobada = (float) ($materia->nota_aprobada ?? 4);
            $vRec = fake()->randomFloat(2, 1, 10);
            $sRec = $vRec >= $notaAprobada ? 'aprobado' : 'desaprobado';

            NotaCuatrimestre::updateOrCreate([
                'id_materia' => $materia->id_materia,
                'id_usuario' => $estudiante->id_usuario,
                'id_ciclo'   => $ciclo->id_ciclo,
                'periodo'    => 'REC',
            ], [
                'valor'       => $vRec,
                'situacion'   => $sRec,
                'fecha_carga' => $this->fechaAleatoriaEntre($ciclo->fecha_inicio, $ciclo->fecha_fin),
            ]);
            $stats['notasCuatrimestre']++;

            $cierre = $this->calcularCierre($s1, $v1, $s2, $v2, $sRec, $vRec, $esPromocionDirecta);
        }

        // Fallback defensivo: la única forma de llegar acá con situación
        // null es la combinación inconsistente "promocion_directa pero la
        // materia no es de promoción directa", que no debería pasar porque
        // $situacionesValidas ya restringe las opciones por tipo de
        // materia. Si igual pasara, no dejamos el registro sin cerrar.
        $situacionFinal = $cierre['situacion'] ?? 'desaprobado';
        $valorFinal      = $cierre['valor'] ?? $this->promediar([$v1, $v2]);

        NotaCuatrimestre::updateOrCreate([
            'id_materia' => $materia->id_materia,
            'id_usuario' => $estudiante->id_usuario,
            'id_ciclo'   => $ciclo->id_ciclo,
            'periodo'    => 'CIERRE',
        ], [
            'valor'       => $valorFinal,
            'situacion'   => $situacionFinal,
            'fecha_carga' => $this->fechaAleatoriaEntre($ciclo->fecha_inicio, $ciclo->fecha_fin),
        ]);
        $stats['notasCuatrimestre']++;

        return $situacionFinal;
    }

    /**
     * Elige una situación válida al azar (ponderada) y un valor numérico
     * coherente con esa situación, para simular la carga de un cuatrimestre.
     */
    private function generarNotaCuatrimestreAleatoria(array $situacionesValidas): array
    {
        $pesos = [
            'promocionado'      => 30,
            'aprobado'          => 40,
            'desaprobado'       => 20,
            'promocion_directa' => 30,
        ];

        $opciones = array_intersect_key($pesos, array_flip($situacionesValidas));

        $situacion = $this->pesoAleatorio($opciones);
        $valor     = fake()->randomFloat(2, 4, 10);

        return [$situacion, $valor];
    }

    /**
     * Port directo de calcularCierreFila() en public/js/cargarNota.js.
     * Tiene que devolver exactamente lo mismo que calcularía un docente
     * desde la UI para el mismo par de 1C/2C (+REC si aplica), así los
     * datos sembrados son consistentes con la regla de negocio real.
     */
    private function calcularCierre(
        ?string $s1,
        ?float $v1,
        ?string $s2,
        ?float $v2,
        ?string $sRec,
        ?float $vRec,
        bool $esPromocionDirecta
    ): array {
        if (!$s1 && !$s2) {
            return ['situacion' => null, 'valor' => null];
        }

        if (!$s1 || !$s2) {
            return ['situacion' => null, 'valor' => null];
        }

        if ($s1 === $s2) {
            if ($s1 === 'promocion_directa' && !$esPromocionDirecta) {
                return ['situacion' => null, 'valor' => $this->promediar([$v1, $v2])];
            }

            $situacionesValidas = ['desaprobado', 'aprobado', 'promocionado', 'promocion_directa'];
            $situacion = in_array($s1, $situacionesValidas, true) ? $s1 : null;

            return ['situacion' => $situacion, 'valor' => $this->promediar([$v1, $v2])];
        }

        $situaciones = [$s1, $s2];

        if (in_array('promocion_directa', $situaciones, true)) {
            return ['situacion' => 'desaprobado', 'valor' => $this->promediar([$v1, $v2])];
        }

        if (in_array('promocionado', $situaciones, true) && in_array('aprobado', $situaciones, true)) {
            return ['situacion' => 'aprobado', 'valor' => $this->promediar([$v1, $v2])];
        }

        if (in_array('desaprobado', $situaciones, true)) {
            $valorDelQueAprobo = $s1 === 'desaprobado' ? $v2 : $v1;

            if ($sRec === 'aprobado') {
                return ['situacion' => 'aprobado', 'valor' => $this->promediar([$valorDelQueAprobo, $vRec])];
            }
            if ($sRec === 'desaprobado') {
                return ['situacion' => 'desaprobado', 'valor' => $this->promediar([$valorDelQueAprobo, $vRec])];
            }

            return ['situacion' => 'examen_flotante', 'valor' => null];
        }

        return ['situacion' => null, 'valor' => null];
    }

    /**
     * Port de promediar() en cargarNota.js: promedio simple de los valores
     * no nulos, redondeado a 2 decimales.
     */
    private function promediar(array $valores): ?float
    {
        $nums = array_values(array_filter(
            $valores,
            fn ($v) => $v !== null && is_numeric($v)
        ));

        if (empty($nums)) {
            return null;
        }

        return round(array_sum($nums) / count($nums), 2);
    }

    private function pesoAleatorio(array $pesos): string
    {
        $total = array_sum($pesos);
        $rand = mt_rand(1, $total);

        foreach ($pesos as $clave => $peso) {
            $rand -= $peso;
            if ($rand <= 0) {
                return $clave;
            }
        }

        return array_key_first($pesos);
    }

    /**
     * fake()->dateTimeBetween() explota si $desde queda después de $hasta.
     * Esto pasa fácilmente con fechas reales de ciclos/mesas (ej. "now"
     * comparado contra una fecha de fin de ciclo que todavía no llegó).
     * En vez de armar un rango random imposible, devolvemos $hasta.
     */
    private function fechaAleatoriaEntre($desde, $hasta): \DateTimeInterface
    {
        $desde = $desde instanceof \DateTimeInterface ? $desde : new \DateTime((string) $desde);
        $hasta = $hasta instanceof \DateTimeInterface ? $hasta : new \DateTime((string) $hasta);

        if ($desde >= $hasta) {
            return $hasta;
        }

        return fake()->dateTimeBetween($desde, $hasta);
    }

    /**
     * Crea (o reutiliza) una mesa de examen para esta materia+ciclo, y
     * carga la nota final del alumno en ella.
     *
     * Si el ciclo todavía no terminó (fecha_fin en el futuro respecto a
     * hoy — típicamente el ciclo abierto actual), no se genera nada: no
     * puede existir una mesa de final ya rendida para un cuatrimestre que
     * todavía está en curso.
     */
    private function generarNotaFinal(
        Materia $materia,
        Estudiante $estudiante,
        CicloLectivo $ciclo,
        ?TipoMesaExamen $tipoMesa,
        ?Llamado $llamado,
        array &$stats
    ): void {
        if (!$tipoMesa) {
            return; // no hay ningún tipo de mesa cargado, no podemos crear una
        }

        $finCiclo = $ciclo->fecha_fin instanceof \DateTimeInterface
            ? $ciclo->fecha_fin
            : new \DateTime((string) $ciclo->fecha_fin);

        if ($finCiclo > new \DateTime('now')) {
            return; // el ciclo todavía no terminó, no puede haber final rendido
        }

        $limiteMesa = (clone $finCiclo)->modify('+2 months');
        $fechaMesa = $this->fechaAleatoriaEntre($finCiclo, $limiteMesa);

        $mesa = MesaExamen::firstOrCreate([
            'id_materia' => $materia->id_materia,
            'id_ciclo'   => $ciclo->id_ciclo,
        ], [
            'fecha'                     => $fechaMesa,
            'id_tipo_mesa'              => $tipoMesa->id_tipo_mesa,
            'id_llamado'                => $llamado?->id_llamado,
            'habilitado'                => true,
            'aula'                      => 'Aula ' . rand(1, 20),
            'id_usuario_administracion' => Administrador::inRandomOrder()->first()?->id_usuario,
        ]);

        $aprobo = fake()->boolean(75);

        NotaFinal::updateOrCreate([
            'id_mesa'    => $mesa->id_mesa,
            'id_usuario' => $estudiante->id_usuario,
        ], [
            'id_materia'  => $materia->id_materia,
            'valor'       => $aprobo ? fake()->randomFloat(2, 4, 10) : fake()->randomFloat(2, 1, 3),
            'fecha_carga' => $this->fechaAleatoriaEntre($mesa->fecha, new \DateTime('now')),
        ]);

        $stats['notasFinal']++;
    }
}