<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsistenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'proyecto' => ['nullable', 'string', 'max:100'],
            'compania' => ['nullable', 'string', 'max:150'],
            'puesto' => ['nullable', 'string', 'max:100'],
            'codigo' => ['nullable', 'string', 'max:100'],
            // Hora local capturada en el dispositivo del empleado (no la del servidor).
            'hora_dispositivo' => ['nullable', 'date_format:H:i:s'],
            'ubicacion_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'ubicacion_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'foto' => ['nullable', 'string'],
        ];
    }
}
