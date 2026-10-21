<?php

namespace App\Http\Controllers;

use App\DTOs\Employee\CreateEmployeeDTO;
use App\DTOs\Employee\UpdateEmployeeDTO;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\OrganizationalUnit;
use App\Services\Employee\EmployeeService;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService,
    ) {}

    public function index()
    {
        $this->authorize('employee.view');

        $employees = $this->employeeService->getPaginated(request()->only([
            'search', 'is_technician', 'unit_id', 'status',
        ]));

        $units = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();

        $stats = [
            'total' => Employee::count(),
            'active' => Employee::where('is_active', true)->count(),
            'technicians' => Employee::where('is_technician', true)->where('is_active', true)->count(),
            'with_assets' => Employee::has('assignedAssets')->count(),
        ];

        return view('employees.index', compact('employees', 'units', 'stats'));
    }

    public function create()
    {
        $this->authorize('employee.create');

        $units = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();

        return view('employees.create', compact('units'));
    }

    public function store(StoreEmployeeRequest $request)
    {
        $this->employeeService->create(
            CreateEmployeeDTO::fromArray($request->validated())
        );

        return redirect()->route('employees.index')
            ->with('success', 'Empleado registrado correctamente.');
    }

    public function show(Employee $employee)
    {
        $this->authorize('employee.view');

        $employee->load('organizationalUnit', 'user', 'assignedAssets', 'maintenanceCases');

        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $this->authorize('employee.edit');

        $units = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();

        return view('employees.edit', compact('employee', 'units'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->employeeService->update(
            $employee,
            UpdateEmployeeDTO::fromArray($request->validated())
        );

        return redirect()->route('employees.index')
            ->with('success', 'Empleado actualizado correctamente.');
    }

    public function toggleActive(Employee $employee)
    {
        $this->authorize('employee.edit');

        $this->employeeService->toggleActive($employee);

        return back()->with('success', 'Estado del empleado actualizado.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('employee.delete');

        $this->employeeService->delete($employee);

        return back()->with('success', 'Empleado eliminado correctamente.');
    }
}
