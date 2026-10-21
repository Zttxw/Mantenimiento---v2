<?php

namespace App\Http\Controllers;

use App\DTOs\Asset\CreateAssetDTO;
use App\DTOs\Asset\UpdateAssetDTO;
use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Http\Requests\Asset\StoreAssetRequest;
use App\Http\Requests\Asset\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\OrganizationalUnit;
use App\Services\Asset\AssetService;
use App\Services\Document\DocumentGeneratorService;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function __construct(
        private readonly AssetService $assetService,
        private readonly DocumentGeneratorService $docGenerator,
    ) {}

    public function index()
    {
        $this->authorize('asset.view');

        $assets = $this->assetService->getPaginated(request()->only([
            'search', 'type', 'status', 'unit_id',
        ]));

        $units = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();
        $types = AssetType::cases();
        $statuses = AssetStatus::cases();

        return view('assets.index', compact('assets', 'units', 'types', 'statuses'));
    }

    public function create()
    {
        $this->authorize('asset.create');

        $units = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();
        $types = AssetType::cases();
        $statuses = AssetStatus::cases();
        $conditions = AssetCondition::cases();

        return view('assets.create', compact('units', 'employees', 'types', 'statuses', 'conditions'));
    }

    public function store(StoreAssetRequest $request)
    {
        $this->assetService->create(
            CreateAssetDTO::fromArray($request->validated())
        );

        return redirect()->route('assets.index')
            ->with('success', 'Activo registrado correctamente.');
    }

    public function show(Asset $asset)
    {
        $this->authorize('asset.view');

        $asset->load('organizationalUnit', 'responsible', 'movements', 'maintenanceCases', 'agentDevice');

        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        $this->authorize('asset.edit');

        $units = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();
        $types = AssetType::cases();
        $statuses = AssetStatus::cases();
        $conditions = AssetCondition::cases();

        return view('assets.edit', compact('asset', 'units', 'employees', 'types', 'statuses', 'conditions'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $this->assetService->update(
            $asset,
            UpdateAssetDTO::fromArray($request->validated())
        );

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Activo actualizado correctamente.');
    }

    public function generateFicha(Asset $asset)
    {
        $this->authorize('asset.view');

        $document = $this->docGenerator->generateFichaTecnica($asset, auth()->user());

        $filename = str_replace(['/', '\\', ':'], '-', $document->title).'.pdf';

        return Storage::disk('local')->download($document->file_path, $filename);
    }

    public function generateHistorial(Asset $asset)
    {
        $this->authorize('asset.view');

        $document = $this->docGenerator->generateHistorialMantenimiento($asset, auth()->user());

        $filename = str_replace(['/', '\\', ':'], '-', $document->title).'.pdf';

        return Storage::disk('local')->download($document->file_path, $filename);
    }

    public function pcMonitor()
    {
        $this->authorize('asset.view');

        $assets = Asset::whereIn('asset_type', [
            AssetType::COMPUTADORA->value,
            AssetType::LAPTOP->value,
        ])
            ->with(['agentDevice', 'organizationalUnit', 'responsible'])
            ->search(request('search'))
            ->byUnit(request()->integer('unit_id') ?: null)
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $units = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();

        return view('assets.pc-monitor', compact('assets', 'units'));
    }

    public function destroy(Asset $asset)
    {
        $this->authorize('asset.delete');

        $this->assetService->delete($asset);

        return redirect()->route('assets.index')
            ->with('success', 'Activo eliminado correctamente.');
    }
}
