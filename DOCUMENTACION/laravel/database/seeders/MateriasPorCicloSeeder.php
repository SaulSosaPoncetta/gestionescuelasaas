<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\CicloLectivo;
use App\Models\Materia;
use App\Models\PlanResolucion;
use App\Models\TipoMateria;
use Illuminate\Database\Seeder;

class MateriasPorCicloSeeder extends Seeder
{
    /**
     * Espacios curriculares de cada plan. Se reparten en 3 años curriculares
     * (1°, 2°, 3°) de forma fija: 5 materias por año, con orden_anio estable.
     *
     * Se crea UNA fila de Materia por (carrera, plan, nombre, ciclo): todos los
     * ciclos lectivos (abiertos y cerrados) tienen sus materias, y como el
     * ciclo forma parte de la identidad, correr el seeder más de una vez no
     * duplica nada.
     */
    protected array $nombres = [
        'Programación I',
        'Programación II',
        'Base de Datos',
        'Sistemas Operativos',
        'Redes',
        'Ingeniería de Software',
        'Análisis de Sistemas',
        'Matemática',
        'Estadística',
        'Arquitectura de Computadoras',
        'Seguridad Informática',
        'Laboratorio de Programación',
        'Diseño Web',
        'Algoritmos y Estructuras de Datos',
        'Investigación Operativa',
    ];

    public function run(): void
    {
        $tipoMateria = TipoMateria::firstOrCreate(['descripcion_tipo_materia' => 'Materia']);

        // Todos los ciclos, incluidos los cerrados.
        $ciclos = CicloLectivo::orderBy('anio')->get();

        if ($ciclos->isEmpty()) {
            $this->command->warn('No hay ciclos lectivos cargados. Corré el seeder/factory de CicloLectivo primero.');
            return;
        }

        $planesResolucion = PlanResolucion::with('carrera')->get();

        if ($planesResolucion->isEmpty()) {
            $this->command->warn('No hay planes de resolución (carrera + plan) cargados.');
            return;
        }

        // Se calcula una sola vez en lugar de hacer una consulta por materia.
        $idAdministrador = Administrador::inRandomOrder()->first()?->id_usuario;

        $creadas = 0;

        foreach ($planesResolucion as $planResolucion) {
            foreach ($this->nombres as $indice => $nombreBase) {
                // Reparte las 15 materias en 3 años curriculares: 5 por año.
                $anio      = intdiv($indice, 5) + 1;
                $ordenAnio = ($indice % 5) + 1;

                // La carga horaria es del espacio curricular, no del ciclo:
                // se sortea una vez y se reusa en todos los ciclos.
                $cargaHoraria = Materia::where('id_carrera', $planResolucion->id_carrera)
                    ->where('id_plan', $planResolucion->id_plan)
                    ->where('nombre', $nombreBase)
                    ->value('carga_horaria_anual')
                    ?? fake()->numberBetween(100, 300);

                foreach ($ciclos as $ciclo) {
                    $materia = Materia::firstOrCreate(
                        [
                            // Identidad: carrera + plan + nombre + ciclo.
                            'id_carrera' => $planResolucion->id_carrera,
                            'id_plan'    => $planResolucion->id_plan,
                            'nombre'     => $nombreBase,
                            'id_ciclo'   => $ciclo->id_ciclo,
                        ],
                        [
                            'id_tipo_materia'          => $tipoMateria->id_tipo_materia,
                            'nota_promocionable'       => 7,
                            'nota_aprobada'            => 4,
                            'anio'                     => $anio,
                            'orden_anio'               => $ordenAnio,
                            'carga_horaria_anual'      => $cargaHoraria,
                            'acreditable_final'        => true,
                            'promocionable'            => true,
                            'id_usuario_administrador' => $idAdministrador,
                        ]
                    );

                    if ($materia->wasRecentlyCreated) {
                        $creadas++;
                    }
                }
            }
        }

        $this->command->info(
            "Listo: {$creadas} materias nuevas creadas para {$planesResolucion->count()} plan(es) de resolución en {$ciclos->count()} ciclo(s)."
        );
    }
}