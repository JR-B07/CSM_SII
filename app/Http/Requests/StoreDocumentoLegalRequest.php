<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentoLegalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['IMSS', 'INE', 'RFC', 'CURP', 'comprobante_domicilio', 'contrato', 'acta_nacimiento', 'otro'])],
            'numero_documento' => ['nullable', 'string', 'max:100'],
            'fecha_vencimiento' => ['nullable', 'date'],
            // 10MB máx.: son escaneos/fotos de documentos, no expedientes completos.
            'archivo' => ['required', 'file', 'max:10240'],
        ];
    }
}
