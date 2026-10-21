<?php

namespace App\Http\Controllers;

use App\DTOs\MaintenanceItem\CreateMaintenanceItemDTO;
use App\Enums\DocumentType;
use App\Enums\MaintenanceCaseStatus;
use App\Enums\MaintenanceItemType;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Http\Requests\MaintenanceCase\CloseMaintenanceCaseRequest;
use App\Http\Requests\MaintenanceItem\StoreMaintenanceItemRequest;
use App\Models\Asset;
use App\Models\MaintenanceCase;
use App\Models\MaintenanceTemplate;
use App\Services\MaintenanceCase\MaintenanceCaseService;
use App\Services\MaintenanceTemplate\MaintenanceTemplateService;
use App\Services\Technician\TechnicianService;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    public function __construct(
        private readonly TechnicianService $technicianService,
        private readonly MaintenanceCaseService $maintenanceCaseService,
        private readonly MaintenanceTemplateService $maintenanceTemplateService,
    ) {}

    public function dashboard()
    {
        $employee = auth()->user()->employee;

        $data = $this->technicianService->getDashboardData($employee);

        return view('technicians.dashboard', array_merge(['employee' => $employee], $data));
    }

    public function workQueue()
    {
        $employee = auth()->user()->employee;

        $queues = $this->technicianService->getWorkQueueData($employee);

        return view('technicians.work-queue', compact('employee', 'queues'));
    }

    public function attendAsset()
    {
        $formData = $this->technicianService->getAttendAssetFormData();

        $preselectedAsset = request()->has('asset_id')
            ? Asset::find(request()->integer('asset_id'))
            : null;
        $templates = $this->maintenanceTemplateService->getActiveByType(null);

        return view('technicians.attend-asset', array_merge($formData, [
            'types' => MaintenanceType::cases(),
            'priorities' => MaintenancePriority::cases(),
            'itemTypes' => MaintenanceItemType::cases(),
            'templates' => $templates,
            'employee' => auth()->user()->employee,
            'preselectedAsset' => $preselectedAsset,
        ]));
    }

    public function caseWorkflow(MaintenanceCase $maintenanceCase)
    {
        $this->authorize('maintenance-case.view');

        $maintenanceCase->load('asset', 'asset.organizationalUnit', 'campaign', 'reportedBy', 'assignedTechnician', 'items');
        $templates = $this->maintenanceTemplateService
            ->getActiveByType($maintenanceCase->maintenance_type->value)
            ->load('items');

        return view('technicians.case-workflow', [
            'maintenanceCase' => $maintenanceCase,
            'types' => MaintenanceType::cases(),
            'priorities' => MaintenancePriority::cases(),
            'itemTypes' => MaintenanceItemType::cases(),
            'templates' => $templates,
            'isClosed' => in_array($maintenanceCase->status->value, ['completado', 'cancelado']),
        ]);
    }

    public function caseDetail(MaintenanceCase $maintenanceCase)
    {
        $this->authorize('maintenance-case.view');

        $maintenanceCase->load('asset', 'asset.organizationalUnit', 'campaign', 'reportedBy', 'assignedTechnician', 'items', 'documents');

        $itemTypes = MaintenanceItemType::cases();
        $documentTypes = DocumentType::cases();
        $statuses = MaintenanceCaseStatus::cases();
        $isClosed = in_array($maintenanceCase->status->value, ['completado', 'cancelado']);

        return view('technicians.case-detail', compact(
            'maintenanceCase', 'itemTypes', 'documentTypes', 'statuses', 'isClosed'
        ));
    }

    public function updateProgress(Request $request, MaintenanceCase $maintenanceCase)
    {
        $this->authorize('maintenance-case.edit');

        $data = $request->validate([
            'maintenance_type' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pendiente,en_progreso,en_espera'],
            'problem_description' => ['nullable', 'string', 'max:2000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'actions_taken' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array'],
            'items.*.item_type' => ['required_with:items', 'string'],
            'items.*.name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'close_case' => ['nullable', 'boolean'],
            'conformity_name' => ['nullable', 'string', 'max:200'],
            'conformity_date' => ['nullable', 'date'],
            'next_maintenance_date' => ['nullable', 'date'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! $request->boolean('close_case')) {
            $currentStatus = $maintenanceCase->status->value;
            $requestedStatus = $data['status'] ?? null;

            if ($requestedStatus === 'en_espera') {
                $data['status'] = 'en_espera';
            } elseif (in_array($currentStatus, ['pendiente', 'en_progreso'], true)) {
                $data['status'] = 'en_progreso';
            }
        }

        $this->technicianService->updateProgress($maintenanceCase, $data);

        foreach ($data['items'] ?? [] as $item) {
            $dto = CreateMaintenanceItemDTO::fromArray([
                'maintenance_case_id' => $maintenanceCase->id,
                'item_type' => $item['item_type'],
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => (int) $item['quantity'],
                'unit_cost' => (float) ($item['unit_cost'] ?? 0),
            ]);

            $this->maintenanceCaseService->addItem($maintenanceCase, $dto);
        }

        if ($request->boolean('close_case')) {
            $this->authorize('maintenance-case.close');

            $this->maintenanceCaseService->close($maintenanceCase, [
                'actions_taken' => $data['actions_taken'] ?? $maintenanceCase->actions_taken,
                'conformity_name' => $data['conformity_name'] ?? null,
                'conformity_date' => $data['conformity_date'] ?? null,
                'next_maintenance_date' => $data['next_maintenance_date'] ?? null,
                'total_cost' => $data['total_cost'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        }

        return redirect()
            ->route('tecnico.work-queue')
            ->with('success', $request->boolean('close_case') ? 'Caso cerrado correctamente.' : 'Caso actualizado correctamente.');
    }

    public function storeAttention(Request $request)
    {
        $this->authorize('maintenance-case.create');

        $data = $request->validate([
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'maintenance_type' => ['required', 'string'],
            'priority' => ['required', 'string'],
            'problem_description' => ['required', 'string', 'max:2000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'actions_taken' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'final_status' => ['required', 'in:pendiente,en_progreso,completado'],
            'items' => ['nullable', 'array'],
            'items.*.item_type' => ['required_with:items', 'string'],
            'items.*.name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $case = $this->technicianService->registerAttention($data, auth()->user());

        return redirect()
            ->route('tecnico.cases.workflow', $case)
            ->with('success', "Caso {$case->code} registrado correctamente.");
    }

    public function addItem(StoreMaintenanceItemRequest $request, MaintenanceCase $maintenanceCase)
    {
        $this->authorize('maintenance-case.edit');

        $dto = CreateMaintenanceItemDTO::fromArray(array_merge(
            $request->validated(),
            ['maintenance_case_id' => $maintenanceCase->id]
        ));

        $this->maintenanceCaseService->addItem($maintenanceCase, $dto);

        return redirect()
            ->route('tecnico.cases.workflow', $maintenanceCase)
            ->with('success', 'Item agregado correctamente.');
    }

    public function removeItem(MaintenanceCase $maintenanceCase, int $item)
    {
        $this->authorize('maintenance-case.edit');

        $maintenanceItem = $maintenanceCase->items()->findOrFail($item);
        $this->maintenanceCaseService->removeItem($maintenanceCase, $maintenanceItem);

        return redirect()
            ->route('tecnico.cases.workflow', $maintenanceCase)
            ->with('success', 'Item eliminado correctamente.');
    }

    public function applyTemplate(Request $request, MaintenanceCase $maintenanceCase)
    {
        $this->authorize('maintenance-case.edit');

        $request->validate(['template_id' => 'required|integer|exists:maintenance_templates,id']);

        $template = MaintenanceTemplate::with('items')->findOrFail($request->integer('template_id'));

        foreach ($template->items as $templateItem) {
            $dto = CreateMaintenanceItemDTO::fromArray([
                'maintenance_case_id' => $maintenanceCase->id,
                'item_type' => $templateItem->item_type->value,
                'name' => $templateItem->name,
                'description' => $templateItem->description,
                'quantity' => (int) $templateItem->quantity,
                'unit_cost' => (float) $templateItem->unit_cost,
            ]);

            $this->maintenanceCaseService->addItem($maintenanceCase, $dto);
        }

        return redirect()
            ->route('tecnico.cases.workflow', $maintenanceCase)
            ->with('success', "Plantilla \"{$template->name}\" aplicada correctamente.");
    }

    public function closeCase(CloseMaintenanceCaseRequest $request, MaintenanceCase $maintenanceCase)
    {
        $this->maintenanceCaseService->close($maintenanceCase, $request->validated());

        return redirect()
            ->route('tecnico.work-queue')
            ->with('success', 'Caso cerrado correctamente.');
    }
}
