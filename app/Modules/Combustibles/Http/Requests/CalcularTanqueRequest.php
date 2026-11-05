<?php

namespace App\Modules\Combustibles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalcularTanqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', 'string', 'in:gasolina_regular,gasolina_premium,diesel,glp_vehicular,gnv,balon_gas_10kg'],
            'cantidad' => ['required', 'numeric', 'min:0.5', 'max:500'],
            'tipo_calculo' => ['nullable', 'string', 'in:llenar_tanque,presupuesto_soles'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo de combustible es obligatorio.',
            'tipo.in' => 'El tipo de combustible seleccionado no es válido.',
            'cantidad.required' => 'La cantidad o presupuesto es obligatorio.',
            'cantidad.numeric' => 'La cantidad debe ser un número válido.',
            'cantidad.min' => 'La cantidad mínima a calcular es 0.5.',
            'cantidad.max' => 'La cantidad máxima permitida es 500.',
            'tipo_calculo.in' => 'El tipo de cálculo debe ser llenar_tanque o presupuesto_soles.',
        ];
    }
}
