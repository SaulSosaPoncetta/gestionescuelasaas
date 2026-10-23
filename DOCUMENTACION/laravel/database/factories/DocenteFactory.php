<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Docente>
 */
class DocenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_usuario' => Usuario::inRandomOrder()->first()->id_usuario,
            'nro_legajo' => $this->faker->unique()->numerify('LEG####'),
        ];
    }
}
