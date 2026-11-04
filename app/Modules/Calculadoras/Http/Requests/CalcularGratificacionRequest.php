<?php

namespace App\Modules\Calculadoras\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalcularGratificacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sueldo' => ['required', 'numeric', 'gt:0', 'lte:1000000'],
            'asignacion_familiar' => ['nullable', 'boolean'],
            'meses_trabajados' => ['required', 'integer', 'between:1,6'],
            'promedio_variables' => ['nullable', 'numeric', 'min:0'],
            'tipo_seguro' => ['required', 'string', 'in:essalud,eps'],
        ];
    }

    public function messages(): array
    {
        return [
            'sueldo.required' => 'El sueldo es obligatorio.',
            'sueldo.numeric' => 'El sueldo debe ser un valor numérico.',
            'sueldo.gt' => 'El sueldo debe ser mayor a 0.',
            'sueldo.lte' => 'El sueldo no puede superar 1 000 000.',
            'meses_trabajados.required' => 'Los meses trabajados son obligatorios.',
            'meses_trabajados.integer' => 'Los meses trabajados deben ser un número entero.',
            'meses_trabajados.between' => 'Los meses trabajados deben estar entre 1 y 6.',
            'tipo_seguro.required' => 'El tipo de seguro es obligatorio.',
            'tipo_seguro.in' => 'El tipo de seguro debe ser "essalud" o "eps".',
            'promedio_variables.numeric' => 'El promedio de variables debe ser un número.',
            'promedio_variables.min' => 'El promedio de variables no puede ser negativo.',
        ];
    }
}
