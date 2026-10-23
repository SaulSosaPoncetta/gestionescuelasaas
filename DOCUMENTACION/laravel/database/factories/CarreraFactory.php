<?php

namespace Database\Factories;

use App\Models\Carrera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Carrera>
 */
class CarreraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         $carreras = [
            [
                'nombre' => 'Analista de Sistemas',
                'titulo' => 'Analista de Sistemas',
            ],
            [
                'nombre' => 'Desarrollo de Software',
                'titulo' => 'Técnico Superior en Desarrollo de Software',
            ],
            [
                'nombre' => 'Redes Informáticas',
                'titulo' => 'Técnico Superior en Redes',
            ],
            [
                'nombre' => 'Ciencia de Datos',
                'titulo' => 'Técnico Superior en Ciencia de Datos',
            ],
            [
                'nombre' => 'Seguridad Informática',
                'titulo' => 'Técnico Superior en Seguridad Informática',
            ],
        ];
        
        return $this->faker->unique()->randomElement($carreras);
    }
}
