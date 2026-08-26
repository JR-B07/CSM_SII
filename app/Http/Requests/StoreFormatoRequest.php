<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFormatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(['checklist', 'formato_trabajo', 'trigger'])],
            // PDF/imagen se pueden ver e imprimir directo desde el navegador;
            // Word/Excel se descargan para abrirse en su programa correspondiente.
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ];
    }
}
