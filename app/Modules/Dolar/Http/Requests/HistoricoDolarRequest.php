<?php

namespace App\Modules\Dolar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class HistoricoDolarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'moneda' => ['nullable', 'string', 'size:3'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('desde') && $this->filled('hasta') && ! $validator->errors()->has('desde') && ! $validator->errors()->has('hasta')) {
                    $desde = Carbon::createFromFormat('Y-m-d', (string) $this->input('desde'));
                    $hasta = Carbon::createFromFormat('Y-m-d', (string) $this->input('hasta'));

                    if ($desde && $hasta && $desde->diffInDays($hasta) > 366) {
                        $validator->errors()->add('rango', 'El rango de consulta no puede superar los 366 días.');
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'desde.required' => 'El campo desde es obligatorio.',
            'desde.date_format' => 'El campo desde debe tener el formato YYYY-MM-DD.',
            'hasta.required' => 'El campo hasta es obligatorio.',
            'hasta.date_format' => 'El campo hasta debe tener el formato YYYY-MM-DD.',
            'hasta.after_or_equal' => 'El campo hasta debe ser igual o posterior al campo desde.',
            'moneda.size' => 'La moneda debe ser un código ISO de 3 caracteres (ej. USD).',
        ];
    }
}
