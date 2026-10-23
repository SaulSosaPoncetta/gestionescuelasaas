<?php

namespace Database\Factories;

use App\Models\pais;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<pais>
 */
class PaisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paises = [
            'Argentina',
            'Brasil',
            'Chile',
            'Uruguay',
            'Paraguay',
            'Perú',
            'Colombia',
            'México',
        ];
        return [
            'nombre_pais' => $this->faker->unique()->randomElement($paises),
        ];
    }
}
