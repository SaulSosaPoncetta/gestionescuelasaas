<?php

namespace Database\Factories;

use App\Models\Auditoria;
use App\Models\CategoriaAuditoria;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Auditoria>
 */
class AuditoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_categoria_auditoria' =>
                CategoriaAuditoria::inRandomOrder()
                    ->first()
                    ->id_categoria_auditoria,

            'id_usuario' =>
                Usuario::inRandomOrder()
                    ->first()
                    ->id_usuario,

            'nombre_tabla' => fake()->randomElement([
                'usuarios',
                'materias',
                'carreras',
                'inscripciones_materia',
                'mesas_examen',
                'notas_final',
            ]),

            'id_registro' => rand(1, 100),

            'accion' => fake()->randomElement([
                'insert',
                'update',
                'delete',
            ]),

            'descripcion' => fake()->sentence(),

            'fecha_hora' => fake()->dateTimeBetween(
                '-1 year',
                'now'
            ),
        ];
    }
}
