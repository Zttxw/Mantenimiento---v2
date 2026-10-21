<?php

namespace App\DTOs\Document;

use App\Enums\DocumentType;

readonly class CreateDocumentDTO
{
    public function __construct(
        public DocumentType $document_type,
        public string $reference_type,
        public int $reference_id,
        public string $title,
        public string $file_path,
        public ?string $code,
        public ?int $generated_by,
        public ?array $meta_json,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            document_type: DocumentType::from($data['document_type']),
            reference_type: $data['reference_type'],
            reference_id: $data['reference_id'],
            title: $data['title'],
            file_path: $data['file_path'],
            code: $data['code'] ?? null,
            generated_by: $data['generated_by'] ?? null,
            meta_json: $data['meta_json'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'document_type' => $this->document_type->value,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'title' => $this->title,
            'file_path' => $this->file_path,
            'code' => $this->code,
            'generated_by' => $this->generated_by,
            'generated_at' => now(),
            'meta_json' => $this->meta_json,
        ];
    }
}
