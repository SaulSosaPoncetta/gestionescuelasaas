<?php

namespace Database\Factories;

use App\Models\NotaCuatrimestre;
use App\Models\TipoArchivo;
use App\Models\Estudiante;
use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaCuatrimestre>
 */
class NotaCuatrimestreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'valor' => fake()->randomFloat(2, 1, 10),

            'fecha_carga' => fake()->dateTimeBetween('-1 year', 'now'),

            'id_tipo_archivo' =>
                TipoArchivo::inRandomOrder()->first()?->id_tipo_archivo,

            'id_usuario' =>
                Estudiante::inRandomOrder()->first()->id_usuario,

            'id_materia' =>
                Materia::inRandomOrder()->first()->id_materia,
        ];
    }
}
