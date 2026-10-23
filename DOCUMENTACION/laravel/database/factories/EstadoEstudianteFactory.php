<?php

namespace Database\Factories;

use App\Models\EstadoEstudiante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstadoEstudiante>
 */
class EstadoEstudianteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {   
        $estados = [
            'Activo',
            'Inactivo',
            'Egresado',
            'Suspendido',
            'Abandonó',
        ];
        return [
            'descripcion_estado' => $this->faker->unique()->randomElement($estados),
        ];
    }
}
