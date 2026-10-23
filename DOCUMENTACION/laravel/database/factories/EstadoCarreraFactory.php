<?php

namespace Database\Factories;

use App\Models\EstadoCarrera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstadoCarrera>
 */
class EstadoCarreraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {   
        $estados = [
            'Activa',
            'Inactiva',
            'Suspendida',
            'Finalizada',
        ];
        return [
            'estado' => $this->faker->unique()->randomElement($estados),
        ];
    }
}
