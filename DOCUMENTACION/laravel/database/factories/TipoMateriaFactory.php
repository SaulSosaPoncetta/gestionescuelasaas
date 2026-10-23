<?php

namespace Database\Factories;

use App\Models\TipoMateria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoMateria>
 */
class TipoMateriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {   
        $tipos = [
            'Obligatoria',
            'Optativa',
            'Electiva',
            'Seminario',
            'Taller',
        ];
        return [
            'descripcion_tipo_materia' => $this->faker->unique()->randomElement($tipos),
        ];
    }
}
