<?php

namespace Database\Factories;

use App\Models\MesaExamen;
use App\Models\TipoMesaExamen;
use App\Models\Llamado;
use App\Models\Materia;
use App\Models\Administrador;
use App\Models\CicloLectivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MesaExamen>
 */
class MesaExamenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // El ciclo se elige primero: su 'anio' es el que determina en qué
        // año cae la fecha final, no puede salir de un rango random.
        $ciclo = CicloLectivo::inRandomOrder()->first();

        $llamado = Llamado::inRandomOrder()->first();

        return [
            'fecha' => $this->fechaParaLlamado($llamado, $ciclo?->anio),

            'id_tipo_mesa' =>
                TipoMesaExamen::inRandomOrder()->first()->id_tipo_mesa,

            'id_llamado' => $llamado?->id_llamado,

            'habilitado' => fake()->boolean(85),

            'id_materia' =>
                Materia::inRandomOrder()->first()->id_materia,

            'aula' => fake()->randomElement([
                'Aula 1',
                'Aula 2',
                'Aula 3',
                'Laboratorio 1',
                'Laboratorio 2',
                'SUM',
            ]),

            'id_usuario_administracion' =>
                Administrador::inRandomOrder()->first()?->id_usuario,

            'id_ciclo' => $ciclo?->id_ciclo,
        ];
    }

    /**
     * Genera una fecha coherente con 'meses_validos' del llamado.
     * Si el llamado no existe o no tiene restricción (Extracurricular,
     * meses_validos = null), cae a un mes cualquiera del año del ciclo.
     */
    protected function fechaParaLlamado(?Llamado $llamado, ?int $anio): \DateTime
    {
        $anio = $anio ?? (int) date('Y');

        $meses = $llamado?->meses_validos;

        $mes = is_array($meses) && count($meses) > 0
            ? fake()->randomElement($meses)
            : fake()->numberBetween(1, 12);

        // Día válido para ese mes/año específico (evita 31 de febrero, etc.)
        $ultimoDia = (int) date('t', mktime(0, 0, 0, $mes, 1, $anio));
        $dia = fake()->numberBetween(1, $ultimoDia);

        $hora = fake()->numberBetween(8, 18);
        $minuto = fake()->randomElement([0, 30]);

        return \DateTime::createFromFormat(
            'Y-m-d H:i',
            sprintf('%04d-%02d-%02d %02d:%02d', $anio, $mes, $dia, $hora, $minuto)
        );
    }

    /**
     * Mesa deshabilitada (validación 'habilitado' del controller).
     */
    public function deshabilitada(): static
    {
        return $this->state(fn () => ['habilitado' => false]);
    }

    /**
     * Fuerza un llamado específico por descripción (ej: 'Extracurricular'),
     * recalculando la fecha para que siga siendo coherente.
     */
    public function conLlamado(string $descripcion): static
    {
        return $this->state(function (array $attributes) use ($descripcion) {
            $llamado = Llamado::where('descripcion', $descripcion)->first();
            $ciclo = CicloLectivo::find($attributes['id_ciclo'] ?? null);

            return [
                'id_llamado' => $llamado?->id_llamado,
                'fecha' => $this->fechaParaLlamado($llamado, $ciclo?->anio),
            ];
        });
    }

    /**
     * Mesa ya rendida (fecha en el pasado) — útil para finalesHistorial().
     * Reemplaza la fecha calculada, ignorando temporalmente el llamado,
     * ya que acá el requisito es "pasada" por sobre "coherente con el mes".
     */
    public function pasada(): static
    {
        return $this->state(fn () => [
            'fecha' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }
}