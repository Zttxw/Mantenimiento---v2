<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Http\Requests\Document\StoreDocumentRequest;
use App\Models\Document;
use App\Services\Document\DocumentService;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
    ) {}

    public function index()
    {
        $this->authorize('document.view');

        $documents = $this->documentService->getPaginated(request()->only([
            'type', 'reference_type', 'reference_id',
        ]));

        $types = DocumentType::cases();

        return view('documents.index', compact('documents', 'types'));
    }

    public function store(StoreDocumentRequest $request)
    {
        $data = array_merge($request->safe()->except('file'), [
            'generated_by' => auth()->id(),
        ]);

        $this->documentService->storeWithFile($data, $request->file('file'));

        return back()->with('success', 'Documento subido correctamente.');
    }

    public function download(Document $document)
    {
        $this->authorize('document.view');

        return response()->download(storage_path('app/'.$document->file_path), $document->title);
    }

    public function destroy(Document $document)
    {
        $this->authorize('document.delete');

        $this->documentService->delete($document);

        return back()->with('success', 'Documento eliminado correctamente.');
    }
}
