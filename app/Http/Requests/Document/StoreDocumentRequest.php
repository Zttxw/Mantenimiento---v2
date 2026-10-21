<?php

namespace App\Http\Requests\Document;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('document.create');
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', new Enum(DocumentType::class)],
            'reference_type' => ['required', 'string'],
            'reference_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:200'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xlsx,jpg,png', 'max:10240'],
            'code' => ['nullable', 'string', 'max:50'],
            'meta_json' => ['nullable', 'array'],
        ];
    }
}
