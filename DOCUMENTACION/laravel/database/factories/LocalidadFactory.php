<?php

namespace Database\Factories;

use App\Models\Localidad;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Pais;

/**
 * @extends Factory<Localidad>
 */
class LocalidadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pais' => Pais::inRandomOrder()->first()->id_pais,
            'codigo_postal' => $this->faker->unique()->numberBetween(1000, 9999),
            'nombre_localidad' => $this->faker->city(),
        ];
    }
}
