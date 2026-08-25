<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // 50MB máx. por spec (tamaño en KB para la regla "max" de Laravel).
            'archivo' => ['required', 'file', 'max:51200'],
            'cliente' => ['nullable', 'string', 'max:100'],
            'proyecto' => ['nullable', 'string', 'max:100'],
        ];
    }
}
