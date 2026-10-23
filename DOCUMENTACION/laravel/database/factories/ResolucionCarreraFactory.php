<?php

namespace Database\Factories;

use App\Models\ResolucionCarrera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResolucionCarrera>
 */
class ResolucionCarreraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
             'descripcion' => $this->faker->unique()->randomElement([
                'Resolución Ministerial N° 123/2020',
                'Resolución Académica N° 456/2021',
                'Resolución Institucional N° 789/2022',
                'Resolución Provincial N° 321/2023',
                'Resolución Nacional N° 654/2024',
            ]),
        ];
    }
}
