<?php

namespace App\Modules\Dni\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidarDniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'dni' => ['required', 'string', 'regex:/^[0-9]{8}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dni.required' => 'El campo dni es obligatorio.',
            'dni.regex' => 'El campo dni debe tener exactamente 8 dígitos numéricos.',
        ];
    }
}
