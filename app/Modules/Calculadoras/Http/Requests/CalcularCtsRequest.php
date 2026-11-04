<?php

namespace App\Modules\Calculadoras\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalcularCtsRequest extends FormRequest
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
            'ultima_gratificacion' => ['nullable', 'numeric', 'min:0'],
            'meses' => ['required', 'integer', 'between:0,6'],
            'dias' => ['required', 'integer', 'between:0,29'],
            'promedio_variables' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sueldo.required' => 'El sueldo es obligatorio.',
            'sueldo.numeric' => 'El sueldo debe ser un valor numérico.',
            'sueldo.gt' => 'El sueldo debe ser mayor a 0.',
            'sueldo.lte' => 'El sueldo no puede superar 1 000 000.',
            'meses.required' => 'El número de meses es obligatorio.',
            'meses.integer' => 'El número de meses debe ser un número entero.',
            'meses.between' => 'El número de meses debe estar entre 0 y 6.',
            'dias.required' => 'El número de días es obligatorio.',
            'dias.integer' => 'El número de días debe ser un número entero.',
            'dias.between' => 'El número de días debe estar entre 0 y 29.',
            'ultima_gratificacion.numeric' => 'La última gratificación debe ser un valor numérico.',
            'ultima_gratificacion.min' => 'La última gratificación no puede ser negativa.',
            'promedio_variables.numeric' => 'El promedio de variables debe ser un número.',
            'promedio_variables.min' => 'El promedio de variables no puede ser negativo.',
        ];
    }
}
