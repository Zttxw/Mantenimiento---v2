<?php

namespace App\Services\Document;

use App\DTOs\Document\CreateDocumentDTO;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return Document::with('generatedBy')
            ->byType($filters['type'] ?? null)
            ->when($filters['reference_type'] ?? null, fn ($q, $rt) => $q->where('reference_type', $rt))
            ->when($filters['reference_id'] ?? null, fn ($q, $id) => $q->where('reference_id', $id))
            ->latest('generated_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function store(CreateDocumentDTO $dto): Document
    {
        return Document::create($dto->toArray());
    }

    public function storeWithFile(array $data, UploadedFile $file): Document
    {
        $path = $file->store('documents/'.date('Y/m'), 'local');

        $dto = CreateDocumentDTO::fromArray(array_merge($data, ['file_path' => $path]));

        return $this->store($dto);
    }

    public function delete(Document $document): void
    {
        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();
    }
}
