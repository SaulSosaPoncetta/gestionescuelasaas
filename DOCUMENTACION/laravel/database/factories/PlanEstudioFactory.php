<?php

namespace Database\Factories;

use App\Models\PlanEstudio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanEstudio>
 */
class PlanEstudioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'titulo' => $this->faker->unique()->randomElement([
                'Plan 2020',
                'Plan 2021',
                'Plan 2022',
                'Plan 2023',
                'Plan 2024',
            ]),
            'descripcion' => $this->faker->paragraph(),
        ];
    }
}
