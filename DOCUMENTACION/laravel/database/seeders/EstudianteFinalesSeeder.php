<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Estudiante;
use App\Models\EstudianteCarrera;
use App\Models\Carrera;
use App\Models\Materia;
use App\Models\Correlativa;
use App\Models\TipoMateria;
use App\Models\NotaCuatrimestre;
use App\Models\InscripcionMateria;
use App\Models\TipoEstudiante;
use App\Models\EstadoEstudiante;
use App\Models\MesaExamen;
use App\Models\TipoMesaExamen;
use App\Models\Llamado;
use App\Models\CicloLectivo;
use App\Models\Administrador;
use App\Models\Docente;
use App\Models\NotaFinal;
use App\Models\PlanResolucion;
use App\Models\PlanEstudio;
use App\Models\ResolucionCarrera;
use App\Models\TipoArchivo;
use Carbon\Carbon;

class EstudianteFinalesSeeder extends Seeder
{
    /**
     * Cuántas materias queremos dejar listas para rendir final.
     * Cambiá este número si querés más o menos.
     */
    private const CANTIDAD_MATERIAS = 5;

    /**
     * Además de las CANTIDAD_MATERIAS "pendientes de inscripción", dejamos
     * 1 mesa ya rendida y APROBADA, y 1 mesa ya rendida y NO APROBADA
     * (pestañas "Aprobado" / "No aprobado" de la vista Finales del alumno).
     *
     * Un final se aprueba con nota >= 4 (fijo, no depende de la materia).
     */

    /**
     * Requiere que UsuarioAdminSeeder ya haya corrido antes (necesita el
     * usuario 'alu@a.com' con su fila en estudiantes, y un administrador
     * para crear la mesa de examen / el ciclo lectivo).
     *
     * Deja al alumno "alu" en condiciones de:
     *   - Verse listado con una carrera activa (id_estado_carrera = 4).
     *   - Tener VARIAS materias con la cursada REGULARIZADA (no promocionada)
     *     Y SIN correlativas obligatorias pendientes, para que
     *     resolverFilaInscripcion() no las bloquee por otro motivo.
     *   - Tener, para cada una, una mesa HABILITADA y con fecha FUTURA.
     *
     * IMPORTANTE: si InscripcionMateriaSeeder corre ANTES que este, puede
     * que ya exista una InscripcionMateria de "alu" para otra materia con
     * id_ciclo distinto al $ciclo usado acá. Por eso usamos updateOrCreate
     * scopeado por id_usuario+id_materia (sin importar el ciclo previo) y
     * forzamos el id_ciclo correcto, para que CursadaEstService pueda
     * matchear inscripción y nota por la misma key "id_materia-id_ciclo".
     */
    public function run(): void
    {
        $usuarioAlu = Usuario::where('email', 'alu@a.com')->first();

        if (!$usuarioAlu) {
            $this->command->error('No se encontró el usuario alu@a.com. Corré UsuarioAdminSeeder antes.');
            return;
        }

        $estudiante = Estudiante::where('id_usuario', $usuarioAlu->id_usuario)->first();

        if (!$estudiante) {
            $this->command->error('El usuario alu@a.com no tiene fila en estudiantes. Corré UsuarioAdminSeeder antes.');
            return;
        }

        $administrador = Administrador::first();

        if (!$administrador) {
            $this->command->error('No hay ningún administrador cargado. Corré UsuarioAdminSeeder antes.');
            return;
        }

        // -------------------------------------------------------------
        // CARRERA: reusa la primera que exista; si no hay ninguna, crea
        // una mínima.
        // -------------------------------------------------------------
        $carrera = Carrera::where('nombre', '!=', 'Sin asignar')->first();

        if (!$carrera) {
            $carrera = Carrera::create([
                'nombre' => 'Redes Informáticas',
            ]);
        }

        // Vincula al alumno con la carrera como ACTIVA (id_estado_carrera = 4).
        $estudianteCarrera = EstudianteCarrera::where('id_usuario', $usuarioAlu->id_usuario)
            ->where('id_carrera', $carrera->id_carrera)
            ->first();

        if ($estudianteCarrera) {
            $estudianteCarrera->cohorte = Carbon::now()->subYears(2)->startOfYear();
            $estudianteCarrera->id_estado_carrera = 4;
            $estudianteCarrera->save();
        } else {
            EstudianteCarrera::create([
                'id_usuario' => $usuarioAlu->id_usuario,
                'id_carrera' => $carrera->id_carrera,
                'cohorte' => Carbon::now()->subYears(2)->startOfYear(),
                'id_estado_carrera' => 4,
            ]);
        }

        // -------------------------------------------------------------
        // PLAN DE ESTUDIO: MateriaFactory arma id_carrera/id_plan a partir
        // de un PlanResolucion al azar, así que si le pisamos id_carrera
        // sin también pisar id_plan con uno que pertenezca a ESTA carrera,
        // quedaría un id_plan de un plan de otra carrera (o inexistente).
        // Por eso buscamos (o creamos) un PlanResolucion propio de
        // $carrera antes de usar la factory más abajo.
        // -------------------------------------------------------------
        $planResolucion = PlanResolucion::where('id_carrera', $carrera->id_carrera)->first();

        if (!$planResolucion) {
            $resolucion = ResolucionCarrera::first() ?? ResolucionCarrera::create([
                'descripcion' => 'Resolución de plan (seed)',
            ]);

            $planEstudio = PlanEstudio::first() ?? PlanEstudio::create([
                'titulo'      => 'Plan de Estudios (seed)',
                'descripcion' => 'Plan de estudios generado por EstudianteFinalesSeeder.',
            ]);

            $tipoArchivoPlan = TipoArchivo::first() ?? TipoArchivo::create([
                'descripcion' => 'Documento de plan (seed)',
                'archivo'     => 'seed/plan-resolucion-dummy.pdf',
            ]);

            $planResolucion = PlanResolucion::create([
                'id_resolucion'   => $resolucion->id_resolucion,
                'id_plan'         => $planEstudio->id_plan,
                'id_carrera'      => $carrera->id_carrera,
                'id_tipo_archivo' => $tipoArchivoPlan->id_tipo_archivo,
                'total_horas'     => 2400,
                'fecha'           => Carbon::now()->subYears(3),
            ]);
        }

        $idPlan = $planResolucion->id_plan;

        // -------------------------------------------------------------
        // MATERIAS: entre las de esa carrera, elegimos varias SIN
        // correlativas bloqueantes (ninguna fila con correlativa_cursada=1
        // ni correlativa_final=1) — si no, aunque regularicemos la cursada,
        // igual van a quedar bloqueadas por la correlativa que les falte.
        // -------------------------------------------------------------
        $idsMateriasConCorrelativaObligatoria = Correlativa::where(function ($q) {
                $q->where('correlativa_cursada', true)
                  ->orWhere('correlativa_final', true);
            })
            ->pluck('id_materia');

        $materias = Materia::where('id_carrera', $carrera->id_carrera)
            ->whereNotIn('id_materia', $idsMateriasConCorrelativaObligatoria)
            ->orderBy('anio')
            ->take(self::CANTIDAD_MATERIAS)
            ->get();

        // Si no hay suficientes materias "limpias" en la carrera, creamos
        // las que falten con la factory. Le pasamos 'nombre' explícito
        // (en vez de dejar que la factory lo genere) para no depender del
        // listado fijo de nombres de MateriaFactory: si otros seeders ya
        // corrieron antes y agotaron ese listado, fake()->unique() revienta
        // con "Maximum retries of 10000 reached without finding a unique
        // value". Al pisar 'nombre' acá, la factory ni intenta resolverlo.
        //
        // OJO: MateriaFactory necesita al menos una fila en tipos_materia
        // (TipoMateria::inRandomOrder()->first()->id_tipo_materia) o
        // revienta con "Attempt to read property on null".
        if ($materias->count() < self::CANTIDAD_MATERIAS) {
            $faltantes = self::CANTIDAD_MATERIAS - $materias->count();

            // Reseteamos el unique() de Faker: si otros seeders ya agotaron el
            // pool fijo de 15 nombres de MateriaFactory, sin este reset la
            // factory revienta con OverflowException apenas la llamamos, antes
            // de que nuestro override de 'nombre' de abajo llegue a aplicarse
            // (definition() siempre corre completo primero).
            fake()->unique($reset = true);

            for ($i = 1; $i <= $faltantes; $i++) {
                $nueva = Materia::factory()->create([
                    'nombre'             => 'Materia Final Seed ' . $carrera->id_carrera . '-' . $i . '-' . uniqid(),
                    'id_carrera'         => $carrera->id_carrera,
                    'id_plan'            => $idPlan,
                    'anio'               => 1,
                    'nota_aprobada'      => 4,
                    'nota_promocionable' => 8,
                ]);

                $materias->push($nueva);
            }
        }

        // -------------------------------------------------------------
        // CATÁLOGOS COMUNES: ciclo lectivo, tipo de mesa, llamado.
        // -------------------------------------------------------------

        // OJO: ciclos_lectivos NO tiene columna 'anio'; las columnas reales
        // son descripcion/fecha_inicio/fecha_fin/id_usuario_administracion.
        $ciclo = CicloLectivo::first() ?? CicloLectivo::create([
            'descripcion'                => 'Ciclo Lectivo ' . date('Y') . ' (seed)',
            'fecha_inicio'               => Carbon::create((int) date('Y'), 3, 1),
            'fecha_fin'                  => Carbon::create((int) date('Y'), 12, 15),
            'id_usuario_administracion'  => $administrador->id_usuario,
        ]);

        $tipoMesa = TipoMesaExamen::first() ?? TipoMesaExamen::create([
            'descripcion' => 'Regular',
        ]);

        $llamado = Llamado::first() ?? Llamado::create([
            'descripcion' => 'Noviembre/Diciembre',
        ]);

        $tipoEstudiante = TipoEstudiante::first();
        $estadoEstudiante = EstadoEstudiante::first();

        $resumen = [];

        // -------------------------------------------------------------
        // Por cada materia: cursada regularizada + inscripción + mesa
        // futura habilitada. Escalonamos un poco las fechas de mesa
        // (15, 17, 19... días) para que no queden todas apiladas el
        // mismo día/hora.
        // -------------------------------------------------------------
        foreach ($materias as $index => $materia) {
            $notaAprobada = (float) ($materia->nota_aprobada ?? 6);
            $notaPromocionable = $materia->nota_promocionable !== null
                ? (float) $materia->nota_promocionable
                : null;

            $valorRegular = $notaPromocionable !== null
                ? max($notaAprobada, $notaPromocionable - 1) // justo por debajo de la promoción
                : $notaAprobada;

            // -----------------------------------------------------------
            // INSCRIPCIÓN A LA MATERIA: CursadaEstService itera sobre
            // InscripcionMateria, no sobre NotaCuatrimestre, así que sin
            // esta fila la materia nunca aparece en ninguna pestaña,
            // aunque la nota esté bien cargada.
            // -----------------------------------------------------------
            InscripcionMateria::updateOrCreate(
                [
                    'id_usuario' => $usuarioAlu->id_usuario,
                    'id_materia' => $materia->id_materia,
                    'id_ciclo'   => $ciclo->id_ciclo,
                ],
                [
                    'fecha_inscripcion'    => Carbon::now()->subMonths(6),
                    'id_tipo_estudiante'   => $tipoEstudiante->id_tipo_estudiante ?? null,
                    'id_estado_estudiante' => $estadoEstudiante->id_estado_estudiante ?? null,
                    'contador_finales'     => 0,
                    'fecha_vencimiento'    => Carbon::now()->addYears(2),
                    'libro'                => 'L-1',
                    'folio'                => 'F-1',
                    'division'             => 1,
                ]
            );

            // -----------------------------------------------------------
            // NOTA CUATRIMESTRAL: mismo id_ciclo que la inscripción de
            // arriba, para que CursadaEstService pueda matchearlas por
            // "id_materia-id_ciclo".
            // -----------------------------------------------------------
            NotaCuatrimestre::updateOrCreate(
                [
                    'id_usuario' => $usuarioAlu->id_usuario,
                    'id_materia' => $materia->id_materia,
                    'id_ciclo'   => $ciclo->id_ciclo,
                ],
                [
                    'valor'       => $valorRegular,
                    'fecha_carga' => Carbon::now()->subMonths(2),
                    'situacion'   => 'Aprobado',
                ]
            );

            // -----------------------------------------------------------
            // MESA DE EXAMEN habilitada y futura para esa materia.
            // Escalonamos la fecha para diferenciarlas entre sí.
            // -----------------------------------------------------------
            $diasHastaMesa = 15 + ($index * 2);

            MesaExamen::firstOrCreate(
                [
                    'id_materia' => $materia->id_materia,
                    'fecha'      => Carbon::now()->addDays($diasHastaMesa)->setTime(9, 0),
                ],
                [
                    'id_tipo_mesa'               => $tipoMesa->id_tipo_mesa,
                    'id_llamado'                 => $llamado->id_llamado,
                    'id_ciclo'                   => $ciclo->id_ciclo,
                    'id_usuario_administracion'  => $administrador->id_usuario,
                    'aula'                       => 'Aula ' . ($index + 1),
                    'habilitado'                 => true,
                ]
            );

            $resumen[] = "'{$materia->nombre}' (nota {$valorRegular}, mesa en {$diasHastaMesa} días)";
        }

        // -------------------------------------------------------------
        // DOCENTE PRESIDENTE DE MESA: doc@a.com (creado por
        // UsuarioAdminSeeder), como 'titular' en mesa_examen_docente.
        // -------------------------------------------------------------
        $usuarioDoc = Usuario::where('email', 'doc@a.com')->first();

        if (!$usuarioDoc) {
            $this->command->error('No se encontró el usuario doc@a.com. Corré UsuarioAdminSeeder antes.');
            return;
        }

        $docente = Docente::where('id_usuario', $usuarioDoc->id_usuario)->first();

        if (!$docente) {
            $this->command->error('El usuario doc@a.com no tiene fila en docentes. Corré UsuarioAdminSeeder antes.');
            return;
        }

        // -------------------------------------------------------------
        // MATERIAS PARA LAS MESAS YA RENDIDAS (aprobada / no aprobada):
        // 2 materias más, distintas de las usadas arriba, también sin
        // correlativas obligatorias bloqueantes.
        // -------------------------------------------------------------
        $idsMateriasUsadas = $materias->pluck('id_materia');

        $materiasFinalRendido = Materia::where('id_carrera', $carrera->id_carrera)
            ->whereNotIn('id_materia', $idsMateriasConCorrelativaObligatoria)
            ->whereNotIn('id_materia', $idsMateriasUsadas)
            ->orderBy('anio')
            ->take(2)
            ->get();

        // Mismo criterio que arriba: 'nombre' explícito para no depender
        // del listado fijo de nombres de MateriaFactory y evitar el
        // OverflowException de fake()->unique().
        if ($materiasFinalRendido->count() < 2) {
            $faltantes = 2 - $materiasFinalRendido->count();

            fake()->unique($reset = true);

            for ($i = 1; $i <= $faltantes; $i++) {
                $nueva = Materia::factory()->create([
                    'nombre'             => 'Materia Final Rendido Seed ' . $carrera->id_carrera . '-' . $i . '-' . uniqid(),
                    'id_carrera'         => $carrera->id_carrera,
                    'id_plan'            => $idPlan,
                    'anio'               => 1,
                    'nota_aprobada'      => 4,
                    'nota_promocionable' => 8,
                ]);

                $materiasFinalRendido->push($nueva);
            }
        }

        // -------------------------------------------------------------
        // Por cada una: mesa YA PASADA (fecha vieja) con doc@a.com como
        // presidente/titular, y notas_final con nota aprobatoria en la
        // primera y desaprobatoria en la segunda.
        // -------------------------------------------------------------
        $resumenFinal = [];

        // Umbral de aprobación de FINAL (distinto de materias.nota_aprobada,
        // que es el mínimo para regularizar/promocionar la CURSADA): un
        // final se aprueba con 4 o más.
        $notaAprobadaFinal = 4;

        foreach ($materiasFinalRendido as $index => $materiaFinal) {
            $esAprobado = $index === 0;

            // Aprobado: 2 puntos por encima del umbral (tope 10).
            // No aprobado: 2 puntos por debajo del umbral (piso 1).
            $valorFinal = $esAprobado
                ? min(10, $notaAprobadaFinal + 2)
                : max(1, $notaAprobadaFinal - 2);

            $diasAtras = 30 + ($index * 5);

            $mesaFinal = MesaExamen::firstOrCreate(
                [
                    'id_materia' => $materiaFinal->id_materia,
                    'fecha'      => Carbon::now()->subDays($diasAtras)->setTime(9, 0),
                ],
                [
                    'id_tipo_mesa'               => $tipoMesa->id_tipo_mesa,
                    'id_llamado'                 => $llamado->id_llamado,
                    'id_ciclo'                   => $ciclo->id_ciclo,
                    'id_usuario_administracion'  => $administrador->id_usuario,
                    'aula'                       => 'Aula Final ' . ($index + 1),
                    'habilitado'                 => true,
                ]
            );

            // doc@a.com como presidente (cargo 'titular') de esta mesa.
            $mesaFinal->docentes()->syncWithoutDetaching([
                $docente->id_usuario => ['cargo' => 'titular'],
            ]);

            NotaFinal::updateOrCreate(
                [
                    'id_mesa'    => $mesaFinal->id_mesa,
                    'id_usuario' => $usuarioAlu->id_usuario,
                ],
                [
                    'id_materia'  => $materiaFinal->id_materia,
                    'valor'       => $valorFinal,
                    'fecha_carga' => Carbon::now()->subDays($diasAtras - 1),
                    'baja'        => false,
                ]
            );

            $resumenFinal[] = "'{$materiaFinal->nombre}' ("
                . ($esAprobado ? 'APROBADO' : 'NO APROBADO')
                . ", nota {$valorFinal}, presidente doc@a.com)";
        }

        $this->command->info(
            "Alu vinculado a carrera '{$carrera->nombre}'. Materias listas para rendir final: "
            . implode(', ', $resumen) . '. '
            . 'Mesas ya rendidas: ' . implode(', ', $resumenFinal) . '.'
        );
    }
}