<?php

namespace App\Modules\Calculadoras\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalcularIgvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'gt:0', 'lte:1000000'],
            'tipo' => ['required', 'string', 'in:base,total'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'monto.gt' => 'El monto debe ser mayor a 0.',
            'monto.lte' => 'El monto no puede superar 1 000 000.',
            'tipo.required' => 'El tipo de cálculo (base o total) es obligatorio.',
            'tipo.in' => 'El tipo de cálculo debe ser "base" o "total".',
        ];
    }
}
