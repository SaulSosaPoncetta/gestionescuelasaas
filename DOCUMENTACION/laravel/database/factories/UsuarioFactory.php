<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Rol;
use App\Models\Localidad;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
          
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'nombre_usuario' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password_hash' => Hash::make('123456'),
            'id_rol' => Rol::inRandomOrder()->first()->id_rol,
            'genero' => $this->faker->randomElement(['M','F','Otro']),
            'estado' => 'Activo',
            'id_localidad' => Localidad::inRandomOrder()->first()?->id_localidad,
            'fecha_nacimiento' => $this->faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'domicilio' => $this->faker->streetAddress(),
        ];
    }
}