<?php

namespace Database\Factories;

use App\Models\TipoMesaExamen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoMesaExamen>
 */
class TipoMesaExamenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {   
        $tipos = [
            'Final Regular',
            'Final Libre',
            'Recuperatorio',
            'Especial',
        ];
        return [
            'descripcion' => $this->faker->unique()->randomElement($tipos),
        ];
    }
}
