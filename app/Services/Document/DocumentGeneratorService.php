<?php

namespace App\Services\Document;

use App\DTOs\Document\CreateDocumentDTO;
use App\Enums\DocumentType;
use App\Models\Asset;
use App\Models\Document;
use App\Models\MaintenanceCampaign;
use App\Models\MaintenanceCase;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentGeneratorService
{
    public function generateInformeTecnico(MaintenanceCase $case, User $generatedBy): Document
    {
        $case->load('asset.organizationalUnit', 'assignedTechnician', 'reportedBy', 'items', 'campaign');

        $pdf = Pdf::loadView('documents.pdf.informe-tecnico', ['case' => $case])
            ->setPaper('a4', 'portrait');

        $filename = 'INFORME-'.$case->code.'.pdf';
        $path = 'documents/'.date('Y/m').'/'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        $code = 'INF-'.strtoupper(Str::random(8));

        $dto = CreateDocumentDTO::fromArray([
            'document_type' => DocumentType::INFORME_TECNICO->value,
            'reference_type' => 'maintenance_case',
            'reference_id' => $case->id,
            'title' => 'Informe Técnico — '.$case->code,
            'file_path' => $path,
            'code' => $code,
            'generated_by' => $generatedBy->id,
            'meta_json' => [
                'case_code' => $case->code,
                'asset_name' => $case->asset?->name,
            ],
        ]);

        return Document::create($dto->toArray());
    }

    public function generateActaMantenimiento(
        MaintenanceCampaign $campaign,
        OrganizationalUnit $unit,
        User $generatedBy,
    ): Document {
        $campaign->load('coordinator', 'createdBy');

        $unitIds = $this->collectUnitIds($unit);

        $assets = Asset::whereIn('organizational_unit_id', $unitIds)
            ->with([
                'organizationalUnit',
                'responsible',
                'campaignAssets' => fn ($q) => $q->where('campaign_id', $campaign->id)
                    ->with('assignedTechnician', 'attendedByEmployee'),
            ])
            ->orderBy('name')
            ->get();

        $unit->load('parent');

        $pdf = Pdf::loadView('documents.pdf.acta-mantenimiento', [
            'campaign' => $campaign,
            'unit' => $unit,
            'assets' => $assets,
        ])->setPaper('a4', 'portrait');

        $filename = 'ACTA-'.$campaign->code.'-'.Str::slug($unit->name).'.pdf';
        $path = 'documents/'.date('Y/m').'/'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        $code = 'ACTA-'.strtoupper(Str::random(8));

        $dto = CreateDocumentDTO::fromArray([
            'document_type' => DocumentType::ACTA_MANTENIMIENTO->value,
            'reference_type' => 'maintenance_campaign',
            'reference_id' => $campaign->id,
            'title' => 'Acta de Mantenimiento — '.$campaign->code.' / '.$unit->name,
            'file_path' => $path,
            'code' => $code,
            'generated_by' => $generatedBy->id,
            'meta_json' => [
                'campaign_code' => $campaign->code,
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'assets_count' => $assets->count(),
            ],
        ]);

        return Document::create($dto->toArray());
    }

    public function generateFichaTecnica(Asset $asset, User $generatedBy): Document
    {
        $asset->load('organizationalUnit', 'responsible', 'agentDevice', 'movements.destinationUnit');

        $pdf = Pdf::loadView('documents.pdf.ficha-tecnica', ['asset' => $asset])
            ->setPaper('a4', 'portrait');

        $filename = 'FICHA-'.$asset->asset_code.'.pdf';
        $path = 'documents/'.date('Y/m').'/'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        $code = 'FT-'.strtoupper(Str::random(8));

        $dto = CreateDocumentDTO::fromArray([
            'document_type' => DocumentType::FICHA_TECNICA->value,
            'reference_type' => 'asset',
            'reference_id' => $asset->id,
            'title' => 'Ficha Técnica — '.$asset->name,
            'file_path' => $path,
            'code' => $code,
            'generated_by' => $generatedBy->id,
            'meta_json' => [
                'asset_code' => $asset->asset_code,
                'asset_type' => $asset->asset_type->value,
            ],
        ]);

        return Document::create($dto->toArray());
    }

    public function generateHistorialMantenimiento(Asset $asset, User $generatedBy): Document
    {
        $asset->load([
            'organizationalUnit',
            'responsible',
            'maintenanceCases' => fn ($q) => $q->with('assignedTechnician', 'items', 'campaign')
                ->orderBy('created_at'),
        ]);

        $pdf = Pdf::loadView('documents.pdf.historial-mantenimiento', ['asset' => $asset])
            ->setPaper('a4', 'portrait');

        $filename = 'HIST-'.$asset->asset_code.'-'.date('Ymd').'.pdf';
        $path = 'documents/'.date('Y/m').'/'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        $code = 'HM-'.strtoupper(Str::random(8));

        $dto = CreateDocumentDTO::fromArray([
            'document_type' => DocumentType::HISTORIAL_MANTENIMIENTO->value,
            'reference_type' => 'asset',
            'reference_id' => $asset->id,
            'title' => 'Historial de Mantenimiento — '.$asset->name,
            'file_path' => $path,
            'code' => $code,
            'generated_by' => $generatedBy->id,
            'meta_json' => [
                'asset_code' => $asset->asset_code,
                'cases_count' => $asset->maintenanceCases->count(),
                'generated_date' => now()->toDateString(),
            ],
        ]);

        return Document::create($dto->toArray());
    }

    private function collectUnitIds(OrganizationalUnit $unit): array
    {
        $ids = [$unit->id];
        $children = OrganizationalUnit::where('parent_id', $unit->id)->get();

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->collectUnitIds($child));
        }

        return $ids;
    }
}
