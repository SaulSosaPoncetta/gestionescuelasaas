<?php

namespace Database\Factories;

use App\Models\Administrador;
use App\Models\Carrera;
use App\Models\CicloLectivo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

class CicloLectivoFactory extends Factory
{
    protected $model = CicloLectivo::class;

    protected ?int $cantidadCarreras = null;

    public function definition(): array
    {
        // Fallback por si se usa sin sequence: año random entre 2020 y el actual
        $anio = fake()->numberBetween(2020, now()->year);

        return $this->datosPorAnio($anio);
    }

    /**
     * Arma el array de datos a partir de un año dado.
     * Solo el año actual queda 'abierto', el resto 'cerrado'.
     */
    protected function datosPorAnio(int $anio): array
    {
        return [
            'anio' => $anio,
            'descripcion' => "Ciclo Lectivo {$anio}",

            'fecha_inicio' => "{$anio}-03-01",
            'fecha_fin' => "{$anio}-12-15",

            'inicio_matriculacion' => "{$anio}-02-01",
            'fin_matriculacion' => "{$anio}-02-28",

            'estado' => $anio === now()->year ? 'abierto' : 'cerrado',

            'id_usuario_administracion' =>
                Administrador::inRandomOrder()->first()?->id_usuario,
        ];
    }

    /**
     * Genera un ciclo por cada año en el rango 2020-2026 (o el que se indique),
     * en orden, sin repetir.
     *
     * Uso:
     *   CicloLectivo::factory()->paraAnios(2020, 2026)->create();
     *   // crea 7 registros, uno por año, 2026 'abierto' y el resto 'cerrado'
     */
    public function paraAnios(int $desde, int $hasta): static
    {
        $anios = range($desde, $hasta);

        return $this->state(new Sequence(
            ...array_map(fn ($anio) => $this->datosPorAnio($anio), $anios)
        ))->count(count($anios));
    }

    public function configure(): static
    {
        return $this->afterCreating(function (CicloLectivo $ciclo) {

            $cantidad = $this->cantidadCarreras ?? rand(2, 4);

            $carreras = Carrera::where('nombre', '!=', Carrera::NOMBRE_SIN_ASIGNAR)
                ->inRandomOrder()
                ->take($cantidad)
                ->pluck('id_carrera');

            if ($carreras->count() < $cantidad) {

                $faltantes = $cantidad - $carreras->count();

                $nuevas = Carrera::factory()
                    ->count($faltantes)
                    ->create()
                    ->pluck('id_carrera');

                $carreras = $carreras->merge($nuevas);
            }

            $ciclo->carreras()->sync($carreras);
        });
    }

    public function conCarreras(int $cantidad): static
    {
        $clone = clone $this;
        $clone->cantidadCarreras = $cantidad;

        return $clone;
    }
}