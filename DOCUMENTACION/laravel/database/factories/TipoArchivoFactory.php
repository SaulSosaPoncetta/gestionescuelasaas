<?php

namespace Database\Factories;

use App\Models\TipoArchivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoArchivo>
 */
class TipoArchivoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {   
         $tipos = [
            'Programa',
            'Guía de Trabajos Prácticos',
            'Bibliografía',
            'Resolución',
            'Examen',
        ];
        return [
            'descripcion' => $this->faker->unique()->randomElement($tipos),
            'archivo' => null,
        ];
    }
}
