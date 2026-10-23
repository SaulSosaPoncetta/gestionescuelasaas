<?php

namespace Database\Factories;

use App\Models\CategoriaAuditoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaAuditoria>
 */
class CategoriaAuditoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         $categorias = [
            'Alta',
            'Media',
            'Baja',
            'Crítica',
            'Informativa',
        ];
        return [
            'descripcion_categoria' => $this->faker->unique()->randomElement($categorias),
        ];
    }
}
