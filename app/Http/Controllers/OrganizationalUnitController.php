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
            'with_assets' => OrganizationalUnit::has('assets')->count(),
        ];

        return view('organizational-units.index', compact('units', 'treeData', 'stats'));
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
        $this->service->create(
            OrganizationalUnitDTO::fromArray($request->validated())
        );

        return redirect()->route('organizational-units.index')
            ->with('success', 'Unidad organizacional creada correctamente.');
    }

    public function show(OrganizationalUnit $organizationalUnit)
    {
        $this->authorize('org-unit.view');

        $organizationalUnit->load('parent', 'children', 'responsible', 'empoyees', 'assets');

        return view('organizational-units.show', compact('organizationalUnit'));
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
