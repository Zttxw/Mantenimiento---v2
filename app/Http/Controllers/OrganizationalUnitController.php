<?php

namespace App\Http\Controllers;

use App\DTOs\OrganizationalUnitDTO;
use App\Http\Requests\OrganizationalUnit\StoreOrganizationalUnitRequest;
use App\Http\Requests\OrganizationalUnit\UpdateOrganizationalUnitRequest;
use App\Models\Employee;
use App\Models\OrganizationalUnit;
use App\Services\OrganizationalUnit\OrganizationalUnitService;

class OrganizationalUnitController extends Controller
{
    public function __construct(
        private readonly OrganizationalUnitService $service,
    ) {}

    public function index()
    {
        $this->authorize('org-unit.view');

        $units = $this->service->getPaginated(request()->only(['search', 'type', 'status']));
        $treeData = $this->service->getTreeArray();

        $stats = [
            'total' => OrganizationalUnit::count(),
            'active' => OrganizationalUnit::where('is_active', true)->count(),
            'with_responsible' => OrganizationalUnit::whereNotNull('responsible_employee_id')->count(),
            'total_employees' => Employee::where('is_active', true)->count(),
        ];

        $allParents = OrganizationalUnit::where('is_active', true)->orderBy('name')->select('id', 'name', 'code')->get();
        $employees = Employee::where('is_active', true)->orderBy('full_name')->select('id', 'full_name', 'position')->get();
        $unitTypes = \App\Enums\OrgUnitType::cases();

        return view('organizational-units.index', compact('units', 'treeData', 'stats', 'allParents', 'employees', 'unitTypes'));
    }

    public function create()
    {
        $this->authorize('org-unit.create');

        $parents = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();

        return view('organizational-units.create', compact('parents', 'employees'));
    }

    public function store(StoreOrganizationalUnitRequest $request)
    {
        $unit = $this->service->create(
            OrganizationalUnitDTO::fromArray($request->validated())
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Dependencia creada correctamente.',
                'unit' => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'code' => $unit->code,
                    'type_label' => $unit->type->label(),
                    'parent_id' => $unit->parent_id,
                ],
            ], 201);
        }

        return redirect()->route('organizational-units.index')
            ->with('success', 'Unidad organizacional creada correctamente.');
    }

    public function show(OrganizationalUnit $organizationalUnit)
    {
        $this->authorize('org-unit.view');

        $organizationalUnit->load('parent', 'children', 'responsible', 'empoyees', 'assets');

        return view('organizational-units.show', compact('organizationalUnit'));
    }

    public function quickInfo(OrganizationalUnit $organizationalUnit)
    {
        $this->authorize('org-unit.view');

        $organizationalUnit->load([
            'parent:id,name',
            'children:id,name,code,type,is_active',
            'responsible:id,full_name,position,dni,email',
            'empoyees:id,full_name,position,dni,email,organizational_unit_id,is_technician',
            'assets:id,name,internal_code,asset_type,brand,model,status,organizational_unit_id,responsible_employee_id',
            'assets.responsibleEmployee:id,full_name',
        ]);

        return response()->json([
            'id' => $organizationalUnit->id,
            'name' => $organizationalUnit->name,
            'code' => $organizationalUnit->code,
            'type' => $organizationalUnit->type->value,
            'type_label' => $organizationalUnit->type->label(),
            'full_path' => $organizationalUnit->full_path,
            'is_active' => (bool) $organizationalUnit->is_active,
            'parent' => $organizationalUnit->parent ? [
                'id' => $organizationalUnit->parent->id,
                'name' => $organizationalUnit->parent->name,
            ] : null,
            'responsible' => $organizationalUnit->responsible ? [
                'id' => $organizationalUnit->responsible->id,
                'name' => $organizationalUnit->responsible->full_name,
                'position' => $organizationalUnit->responsible->position,
                'dni' => $organizationalUnit->responsible->dni,
                'email' => $organizationalUnit->responsible->email,
            ] : null,
            'children' => $organizationalUnit->children->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'type_label' => $c->type->label(),
                'is_active' => (bool) $c->is_active,
            ])->values(),
            'employees' => $organizationalUnit->empoyees->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->full_name,
                'position' => $e->position,
                'dni' => $e->dni,
                'email' => $e->email,
                'is_technician' => (bool) $e->is_technician,
                'show_url' => route('employees.show', $e),
            ])->values(),
            'assets' => $organizationalUnit->assets->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'internal_code' => $a->internal_code,
                'type_label' => $a->asset_type?->label() ?? 'Equipo',
                'brand' => $a->brand,
                'model' => $a->model,
                'status_label' => $a->status?->label() ?? 'Operativo',
                'responsible_name' => $a->responsibleEmployee?->full_name,
                'show_url' => route('assets.show', $a),
            ])->values(),
            'show_url' => route('organizational-units.show', $organizationalUnit),
            'edit_url' => route('organizational-units.edit', $organizationalUnit),
        ]);
    }

    public function edit(OrganizationalUnit $organizationalUnit)
    {
        $this->authorize('org-unit.edit');

        $parents = OrganizationalUnit::where('is_active', true)
            ->where('id', '!=', $organizationalUnit->id)
            ->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();

        return view('organizational-units.edit', compact('organizationalUnit', 'parents', 'employees'));
    }

    public function update(UpdateOrganizationalUnitRequest $request, OrganizationalUnit $organizationalUnit)
    {
        $this->service->update(
            $organizationalUnit,
            OrganizationalUnitDTO::fromArray($request->validated())
        );

        return redirect()->route('organizational-units.index')
            ->with('success', 'Unidad organizacional actualizada correctamente.');
    }

    public function destroy(OrganizationalUnit $organizationalUnit)
    {
        $this->authorize('org-unit.delete');

        $this->service->delete($organizationalUnit);

        return back()->with('success', 'Unidad organizacional eliminada correctamente.');
    }
}
