<?php

namespace App\Modules\Dolar\Http\Requests;

use App\Modules\Dolar\Services\DolarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertirMonedasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'de' => is_string($this->input('de')) ? strtoupper(trim($this->input('de'))) : $this->input('de'),
            'a' => is_string($this->input('a')) ? strtoupper(trim($this->input('a'))) : $this->input('a'),
        ]);
    }

    public function rules(): array
    {
        $currencies = DolarService::SUPPORTED_CURRENCIES;

        return [
            'de' => ['required', 'string', 'size:3', Rule::in($currencies)],
            'a' => ['required', 'string', 'size:3', Rule::in($currencies)],
            'monto' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'de.required' => 'La moneda de origen (de) es obligatoria.',
            'de.in' => 'La moneda de origen no está soportada. Permitidas: ' . implode(', ', DolarService::SUPPORTED_CURRENCIES) . '.',
            'a.required' => 'La moneda de destino (a) es obligatoria.',
            'a.in' => 'La moneda de destino no está soportada. Permitidas: ' . implode(', ', DolarService::SUPPORTED_CURRENCIES) . '.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'monto.gt' => 'El monto debe ser mayor a cero.',
        ];
    }
}
