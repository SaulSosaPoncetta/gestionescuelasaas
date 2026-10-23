<?php

namespace Database\Factories;

use App\Models\NotaFinal;
use App\Models\MesaExamen;
use App\Models\TipoArchivo;
use App\Models\Estudiante;
use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaFinal>
 */
class NotaFinalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_mesa' =>
                MesaExamen::inRandomOrder()->first()->id_mesa,

            'valor' => fake()->randomFloat(2, 1, 10),

            'id_tipo_archivo' =>
                TipoArchivo::inRandomOrder()->first()?->id_tipo_archivo,

            'fecha_carga' =>
                fake()->dateTimeBetween('-1 year', 'now'),

            'id_usuario' =>
                Estudiante::inRandomOrder()->first()->id_usuario,

            'id_materia' =>
                Materia::inRandomOrder()->first()->id_materia,
        ];
    }
}
