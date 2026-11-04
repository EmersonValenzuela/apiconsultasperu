<?php

namespace App\Modules\Feriados\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListarFeriadosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currentYear = (int) now('America/Lima')->format('Y');
        $min = $currentYear - 1;
        $max = $currentYear + 1;

        return [
            'anio' => ['nullable', 'integer', "between:{$min},{$max}"],
        ];
    }

    public function messages(): array
    {
        $currentYear = (int) now('America/Lima')->format('Y');
        $min = $currentYear - 1;
        $max = $currentYear + 1;

        return [
            'anio.integer' => 'El año debe ser un número entero.',
            'anio.between' => "El rango de año permitido es de {$min} a {$max}.",
        ];
    }
}
