<?php

namespace Database\Factories;

use App\Models\TipoEstudiante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoEstudiante>
 */
class TipoEstudianteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {   
        $tipos = [
            'Regular',
            'Libre',
            'Condicional',
            'Oyente',
        ];
        return [
            'descripcion_tipo' => $this->faker->unique()->randomElement($tipos),
        ];
    }
}
