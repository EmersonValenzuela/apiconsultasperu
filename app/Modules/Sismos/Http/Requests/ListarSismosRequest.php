<?php

namespace App\Modules\Sismos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListarSismosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'min_magnitud' => ['nullable', 'numeric', 'min:0'],
            'dias' => ['nullable', 'integer', 'between:1,30'],
            'limite' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'min_magnitud.numeric' => 'La magnitud mínima debe ser un número.',
            'min_magnitud.min' => 'La magnitud mínima no puede ser negativa.',
            'dias.integer' => 'El número de días debe ser un entero.',
            'dias.between' => 'El rango de días permitido es de 1 a 30.',
            'limite.integer' => 'El límite debe ser un entero.',
            'limite.between' => 'El límite de resultados debe estar entre 1 y 100.',
        ];
    }
}
