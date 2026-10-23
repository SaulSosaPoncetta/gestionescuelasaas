<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCicloLectivoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'anio' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($this->fecha_inicio) {
                        $anioInicio = (int) \Carbon\Carbon::parse($this->fecha_inicio)->format('Y');
                        if ((int) $value !== $anioInicio) {
                            $fail("El año debe coincidir con el año de la fecha de inicio ({$anioInicio}).");
                        }
                    }
                },
                Rule::unique('ciclos_lectivos', 'anio')
                    ->ignore($this->route('id'), 'id_ciclo'),
            ],

            'fecha_inicio' => 'required|date',

            'fecha_fin' => 'required|date|after:fecha_inicio',

            'inicio_matriculacion' => 'required|date|before_or_equal:fecha_inicio',

            'fin_matriculacion' => 'required|date|after:inicio_matriculacion|before_or_equal:fecha_fin',
        ];
    }

    public function messages(): array
    {
        return [
            'anio.required' => 'Debe ingresar un año.',
            'anio.integer' => 'El año debe ser un número.',
            'anio.unique' => 'Ya existe un ciclo lectivo para ese año.',

            'fecha_inicio.required' => 'Debe ingresar la fecha de inicio.',
            'fecha_fin.required' => 'Debe ingresar la fecha de fin.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'inicio_matriculacion.before_or_equal' => 'La matriculación debe comenzar antes o el mismo día que inicia el ciclo lectivo.',

            'inicio_matriculacion.required' => 'Debe ingresar la fecha de inicio de matriculación.',
            'fin_matriculacion.required' => 'Debe ingresar la fecha de fin de matriculación.',
            'fin_matriculacion.after' => 'La fecha de fin de matriculación debe ser posterior a la fecha de inicio de matriculación.',
            'fin_matriculacion.before_or_equal' => 'la matriculación no puede finalizar después del fin del ciclo lectivo.',
        ];
    }
}