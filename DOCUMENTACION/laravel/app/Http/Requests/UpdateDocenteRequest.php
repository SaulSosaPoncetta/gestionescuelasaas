<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateDocenteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene el id_usuario del profesor desde el parámetro de ruta,
     * sin depender de cómo se haya nombrado ({id}, {docente}, etc).
     */
    protected function idUsuario(): ?int
    {
        $parametros = $this->route()?->parameters() ?? [];

        return $parametros ? (int) reset($parametros) : null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->idUsuario();

        return [
            'nombre'   => 'required|string|max:100|regex:/^[\pL\s\-]+$/u',
            'apellido' => 'required|string|max:100|regex:/^[\pL\s\-]+$/u',
            'email'            => [
                'required', 'email', 'max:150',
                Rule::unique('usuarios', 'email')->ignore($id, 'id_usuario'),
            ],
            'nombre_usuario'   => [
                'required', 'string', 'max:50',
                Rule::unique('usuarios', 'nombre_usuario')->ignore($id, 'id_usuario'),
            ],
            'password' => ['nullable', Password::min(8)->mixedCase()->numbers()],
            'genero'           => 'nullable|in:Masculino,Femenino,Otro',
            'id_localidad'     => 'nullable|integer|exists:localidades,id_localidad',
            'id_pais'          => 'nullable|integer|exists:paises,id_pais',
            'domicilio'        => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'estado'           => 'nullable|in:Activo,Inactivo',
            'telefono'         => 'nullable|numeric|digits_between:10,13',
            'dni'              => [
                'required', 'digits_between:7,8',
                Rule::unique('dnis', 'dni')->where(function ($query) use ($id) {
                    return $query->where('id_usuario', '!=', $id);
                }),
            ],
            'cuil'             => [
                'nullable',
                'regex:/^\d{2}-\d{8}-\d$/',
                Rule::unique('dnis', 'cuil')->where(function ($query) use ($id) {
                    return $query->where('id_usuario', '!=', $id);
                }),
            ],
            'id_tipo_dni'      => 'required|integer|exists:tipos_dni,id_tipo_dni',
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'dni.required'       => 'Debe ingresar un DNI.',
            'dni.digits_between' => 'El DNI debe tener entre 7 y 8 dígitos.',
            'dni.unique'         => 'Ese DNI ya está registrado.',

            'cuil.regex'         => 'El CUIL/CUIT debe tener el formato 20-12345678-3.',
            'cuil.unique'        => 'Ese CUIL/CUIT ya está registrado.',

            'email.unique'          => 'Ese email ya está registrado.',
            'nombre_usuario.unique' => 'Ese nombre de usuario ya está en uso.',
        ];
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
