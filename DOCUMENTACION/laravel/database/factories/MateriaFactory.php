<?php

namespace Database\Factories;

use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\TipoMateria;
use App\Models\Administrador;
use App\Models\PlanResolucion;
use App\Models\CicloLectivo;

/**
 * @extends Factory<Materia>
 */
class MateriaFactory extends Factory
{
    public function definition(): array
    {
        $planResolucion = PlanResolucion::inRandomOrder()->first();

        $cicloLectivo = CicloLectivo::where('estado', 'abierto')->inRandomOrder()->first()
            ?? CicloLectivo::inRandomOrder()->first();

        return [
            'id_tipo_materia' => TipoMateria::inRandomOrder()->first()->id_tipo_materia,

            'nota_promocionable' => 7,

            'nota_aprobada' => 4,

            'nombre' => fake()->unique()->randomElement([
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
                'Investigación Operativa'
            ]),

            'anio' => fake()->numberBetween(1, 3),

            'orden_anio' => fake()->numberBetween(1, 10),

            'carga_horaria_anual' => fake()->numberBetween(100, 300),

            'id_usuario_administrador' =>
                Administrador::inRandomOrder()->first()?->id_usuario,

            'id_carrera' => $planResolucion->id_carrera,

            'id_plan' => $planResolucion->id_plan,

            'id_ciclo' => $cicloLectivo?->id_ciclo,
        ];
    }
}