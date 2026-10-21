<?php

namespace App\Http\Controllers;

use App\DTOs\MaintenanceCampaign\CreateMaintenanceCampaignDTO;
use App\DTOs\MaintenanceCampaign\UpdateMaintenanceCampaignDTO;
use App\Enums\AssetCondition;
use App\Enums\AssetType;
use App\Enums\CampaignStatus;
use App\Http\Requests\MaintenanceCampaign\StoreMaintenanceCampaignRequest;
use App\Http\Requests\MaintenanceCampaign\UpdateMaintenanceCampaignRequest;
use App\Models\Asset;
use App\Models\CampaignAsset;
use App\Models\Employee;
use App\Models\MaintenanceCampaign;
use App\Models\OrganizationalUnit;
use App\Services\Document\DocumentGeneratorService;
use App\Services\MaintenanceCampaign\MaintenanceCampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;

class MaintenanceCampaignController extends Controller
{
    public function __construct(
        private readonly MaintenanceCampaignService $campaignService,
        private readonly DocumentGeneratorService $docGenerator,
    ) {}

    public function index()
    {
        $this->authorize('campaign.view');

        $campaigns = $this->campaignService->getPaginated(request()->only(['search', 'status']));
        $statuses = CampaignStatus::cases();

        return view('campaigns.index', compact('campaigns', 'statuses'));
    }

    public function create()
    {
        $this->authorize('campaign.create');

        $coordinators = Employee::where('is_active', true)->orderBy('full_name')->get();
        $statuses = CampaignStatus::cases();

        return view('campaigns.create', compact('coordinators', 'statuses'));
    }

    public function store(StoreMaintenanceCampaignRequest $request)
    {
        $data = array_merge($request->validated(), [
            'created_by' => auth()->id(),
        ]);

        $campaign = $this->campaignService->create(
            CreateMaintenanceCampaignDTO::fromArray($data)
        );

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaña de mantenimiento creada correctamente.');
    }

    public function show(MaintenanceCampaign $campaign)
    {
        $this->authorize('campaign.view');

        $campaign->load([
            'coordinator',
            'campaignAssets.asset.organizationalUnit',
            'campaignAssets.asset.responsible',
            'campaignAssets.assignedTechnician',
            'campaignAssets.attendedByEmployee',
        ]);

        $technicians = Employee::technicians()->select('id', 'full_name')->orderBy('full_name')->get();
        $organizationalUnits = OrganizationalUnit::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $assetTypes = AssetType::cases();
        $assetConditions = AssetCondition::cases();

        return view('campaigns.show', compact('campaign', 'technicians', 'organizationalUnits', 'assetTypes', 'assetConditions'));
    }

    public function edit(MaintenanceCampaign $campaign)
    {
        $this->authorize('campaign.edit');

        $campaign->load('campaignAssets.asset', 'campaignAssets.assignedTechnician');

        $coordinators = Employee::where('is_active', true)->orderBy('full_name')->get();
        $statuses = CampaignStatus::cases();
        $technicians = Employee::technicians()->orderBy('full_name')->get();
        $organizationalUnits = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();
        $availableAssets = Asset::whereNotIn('id', $campaign->campaignAssets()->pluck('asset_id'))->orderBy('name')->get();

        return view('campaigns.edit', compact('campaign', 'coordinators', 'statuses', 'technicians', 'organizationalUnits', 'availableAssets'));
    }

    public function update(UpdateMaintenanceCampaignRequest $request, MaintenanceCampaign $campaign)
    {
        $this->campaignService->update(
            $campaign,
            UpdateMaintenanceCampaignDTO::fromArray($request->validated())
        );

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaña actualizada correctamente.');
    }

    public function destroy(MaintenanceCampaign $campaign)
    {
        $this->authorize('campaign.delete');

        $this->campaignService->delete($campaign);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaña eliminada correctamente.');
    }

    public function addAsset(MaintenanceCampaign $campaign)
    {
        $this->authorize('campaign.edit');

        $this->campaignService->addAsset(
            $campaign,
            (int) request('asset_id'),
            request()->only(['assigned_technician_id', 'scheduled_date', 'notes'])
        );

        return back()->with('success', 'Activo agregado a la campaña.');
    }

    public function removeAsset(MaintenanceCampaign $campaign, int $assetId)
    {
        $this->authorize('campaign.edit');

        $this->campaignService->removeAsset($campaign, $assetId);

        return back()->with('success', 'Activo removido de la campaña.');
    }

    public function bulkAddByUnit(MaintenanceCampaign $campaign)
    {
        $this->authorize('campaign.edit');

        $count = $this->campaignService->bulkAddByUnit(
            $campaign,
            (int) request('unit_id'),
            request()->only(['assigned_technician_id', 'scheduled_date', 'notes'])
        );

        return back()->with('success', "{$count} activo(s) agregado(s) a la campaña desde la unidad seleccionada.");
    }

    /**
     * Registra manualmente la atención preventiva de un activo durante una campaña activa.
     */
    public function attendAsset(Request $request, MaintenanceCampaign $campaign, CampaignAsset $campaignAsset)
    {
        $this->authorize('campaign.edit');

        $data = $request->validate([
            'patrimonial_code' => ['nullable', 'string', 'max:50'],
            'organizational_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'responsible_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'attended_by_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'attended_date' => ['required', 'date'],
            'checklist_tasks' => ['nullable', 'array'],
            'asset_condition_after' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->campaignService->attendAssetManually($campaign, $campaignAsset, $data, auth()->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Atención preventiva registrada para {$campaignAsset->asset->name}.");
    }

    /**
     * Registra rápidamente un nuevo activo en campo (monitor, impresora, pantalla) y lo asocia a la campaña.
     */
    public function quickRegisterAsset(Request $request, MaintenanceCampaign $campaign)
    {
        $this->authorize('campaign.edit');

        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'asset_type' => ['required', 'string'],
            'patrimonial_code' => ['nullable', 'string', 'max:50', 'unique:assets,patrimonial_code'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', 'string'],
            'organizational_unit_id' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'responsible_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'assigned_technician_id' => ['nullable', 'integer', 'exists:employees,id'],
            'mark_attended' => ['nullable', 'boolean'],
            'checklist_tasks' => ['nullable', 'array'],
            'attended_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $assetData = $request->only([
            'name', 'asset_type', 'patrimonial_code', 'brand', 'model', 'serial_number',
            'condition', 'organizational_unit_id', 'responsible_employee_id', 'notes',
        ]);

        $campaignData = [
            'assigned_technician_id' => $request->input('assigned_technician_id'),
            'mark_attended' => $request->boolean('mark_attended'),
            'checklist_tasks' => $request->input('checklist_tasks', []),
            'attended_date' => $request->input('attended_date', now()->toDateString()),
            'attended_by_employee_id' => $request->input('assigned_technician_id') ?: auth()->user()->employee?->id,
            'notes' => $request->input('notes'),
        ];

        $this->campaignService->quickRegisterAsset($campaign, $assetData, $campaignData, auth()->user());

        return back()->with('success', "Activo \"{$request->name}\" registrado y agregado a la campaña.");
    }

    /**
     * Transición de estados de la campaña (aprobación, activación, pausa, etc.)
     */
    public function updateStatus(Request $request, MaintenanceCampaign $campaign)
    {
        $request->validate([
            'status' => ['required', new Enum(CampaignStatus::class)],
        ]);

        $newStatus = CampaignStatus::from($request->status);

        // Si se aprueba una campaña pendiente, requiere permiso campaign.approve
        if (in_array($newStatus, [CampaignStatus::PLANIFICADA, CampaignStatus::EN_CURSO]) &&
            $campaign->status === CampaignStatus::PENDIENTE_APROBACION) {
            $this->authorize('campaign.approve');
        } else {
            $this->authorize('campaign.edit');
        }

        $this->campaignService->changeStatus($campaign, $newStatus);

        return back()->with('success', "Estado de la campaña actualizado a \"{$newStatus->label()}\".");
    }

    public function generateActa(Request $request, MaintenanceCampaign $campaign)
    {
        $this->authorize('campaign.view');

        $request->validate(['unit_id' => 'required|integer|exists:organizational_units,id']);

        $unit = OrganizationalUnit::findOrFail($request->unit_id);

        $document = $this->docGenerator->generateActaMantenimiento($campaign, $unit, auth()->user());

        $filename = str_replace(['/', '\\', ':'], '-', $document->title).'.pdf';

        return Storage::disk('local')->download($document->file_path, $filename);
    }
}
