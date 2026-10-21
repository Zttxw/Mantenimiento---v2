<?php

namespace App\Http\Controllers;

use App\Enums\MaintenanceItemType;
use App\Enums\MaintenanceType;
use App\Models\MaintenanceTemplate;
use App\Services\MaintenanceTemplate\MaintenanceTemplateService;
use Illuminate\Http\Request;

class MaintenanceTemplateController extends Controller
{
    public function __construct(
        private readonly MaintenanceTemplateService $service,
    ) {}

    public function index()
    {
        $this->authorize('maintenance-template.view');

        $templates = $this->service->getPaginated(request()->only(['type']));
        $types = MaintenanceType::cases();

        return view('maintenance-templates.index', compact('templates', 'types'));
    }

    public function create()
    {
        $this->authorize('maintenance-template.create');

        $types = MaintenanceType::cases();
        $itemTypes = MaintenanceItemType::cases();

        return view('maintenance-templates.create', compact('types', 'itemTypes'));
    }

    public function store(Request $request)
    {
        $this->authorize('maintenance-template.create');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'maintenance_type' => 'nullable|string|in:'.implode(',', array_column(MaintenanceType::cases(), 'value')),
            'description' => 'nullable|string|max:500',
            'problem_description' => 'nullable|string|max:2000',
            'diagnosis_template' => 'nullable|string|max:2000',
            'actions_template' => 'nullable|string|max:2000',
            'steps_json' => 'nullable|array',
            'steps_json.*.title' => 'required_with:steps_json|string|max:200',
            'steps_json.*.detail' => 'nullable|string|max:500',
            'next_maintenance_interval_days' => 'nullable|integer|min:1|max:3650',
            'is_active' => 'boolean',
            'items' => 'nullable|array',
            'items.*.item_type' => 'required_with:items|string|in:'.implode(',', array_column(MaintenanceItemType::cases(), 'value')),
            'items.*.name' => 'required_with:items|string|max:200',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $this->service->create($data, auth()->id());

        return redirect()->route('maintenance-templates.index')
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function edit(MaintenanceTemplate $maintenanceTemplate)
    {
        $this->authorize('maintenance-template.edit');

        $maintenanceTemplate->load('items');
        $types = MaintenanceType::cases();
        $itemTypes = MaintenanceItemType::cases();

        return view('maintenance-templates.edit', compact('maintenanceTemplate', 'types', 'itemTypes'));
    }

    public function update(Request $request, MaintenanceTemplate $maintenanceTemplate)
    {
        $this->authorize('maintenance-template.edit');

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'maintenance_type' => 'nullable|string|in:'.implode(',', array_column(MaintenanceType::cases(), 'value')),
            'description' => 'nullable|string|max:500',
            'problem_description' => 'nullable|string|max:2000',
            'diagnosis_template' => 'nullable|string|max:2000',
            'actions_template' => 'nullable|string|max:2000',
            'steps_json' => 'nullable|array',
            'steps_json.*.title' => 'required_with:steps_json|string|max:200',
            'steps_json.*.detail' => 'nullable|string|max:500',
            'next_maintenance_interval_days' => 'nullable|integer|min:1|max:3650',
            'is_active' => 'boolean',
            'items' => 'nullable|array',
            'items.*.item_type' => 'required_with:items|string|in:'.implode(',', array_column(MaintenanceItemType::cases(), 'value')),
            'items.*.name' => 'required_with:items|string|max:200',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $this->service->update($maintenanceTemplate, $data);

        return redirect()->route('maintenance-templates.index')
            ->with('success', 'Plantilla actualizada correctamente.');
    }

    public function destroy(MaintenanceTemplate $maintenanceTemplate)
    {
        $this->authorize('maintenance-template.delete');

        $this->service->delete($maintenanceTemplate);

        return redirect()->route('maintenance-templates.index')
            ->with('success', 'Plantilla eliminada.');
    }

    public function data(MaintenanceTemplate $maintenanceTemplate)
    {
        $this->authorize('maintenance-case.view');

        $maintenanceTemplate->load('items');

        return response()->json($maintenanceTemplate->toApiArray());
    }

    public function listForType(Request $request)
    {
        $this->authorize('maintenance-case.view');

        $templates = $this->service->getActiveByType($request->input('type'));

        return response()->json($templates->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
        ])->values());
    }
}
