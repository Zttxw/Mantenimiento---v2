<?php

namespace App\Services\Employee;

use App\DTOs\Employee\CreateEmployeeDTO;
use App\DTOs\Employee\UpdateEmployeeDTO;
use App\Enums\RoleEnum;
use App\Exceptions\Employee\EmployeeException;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EmployeeService
{
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return Employee::with('organizationalUnit')
            ->search($filters['search'] ?? null)
            ->when(isset($filters['is_technician']), fn ($q) => $q->where('is_technician', (bool) $filters['is_technician'])
            )
            ->when(isset($filters['unit_id']), fn ($q) => $q->where('organizational_unit_id', $filters['unit_id'])
            )
            ->when(isset($filters['status']), fn ($q) => $filters['status'] === 'active' ? $q->active() : $q->where('is_active', false)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function getAllTechnicians(): Collection
    {
        return Employee::technicians()->orderBy('full_name')->get();
    }

    public function create(CreateEmployeeDTO $dto): Employee
    {
        $employee = Employee::create($dto->toArray());

        if ($dto->is_technician) {
            $this->createTechnicianUser($employee, $dto->dni);
        }

        return $employee;
    }

    private function createTechnicianUser(Employee $employee, string $dni): User
    {
        $firstName = Str::ascii(strtolower(explode(' ', trim($employee->name))[0]));
        $firstSurname = Str::ascii(strtolower(explode(' ', trim($employee->last_name))[0]));

        // Primer letra del nombre + primer apellido (solo letras)
        $base = preg_replace('/[^a-z]/', '', substr($firstName, 0, 1).$firstSurname);
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.$counter++;
        }

        $user = User::create([
            'employee_id' => $employee->id,
            'username' => $username,
            'email' => $employee->email,
            'password' => $dni,
            'is_active' => true,
        ]);

        $user->assignRole(RoleEnum::TECNICO->value);

        return $user;
    }

    public function update(Employee $employee, UpdateEmployeeDTO $dto): Employee
    {
        $employee->update($dto->toArray());

        return $employee->fresh();
    }

    public function toggleActive(Employee $employee): Employee
    {
        $employee->update(['is_active' => ! $employee->is_active]);

        return $employee;
    }

    public function delete(Employee $employee): void
    {
        if ($employee->assignedAssets()->exists()) {
            throw EmployeeException::hasAssignedAssets($employee->full_name);
        }

        $employee->delete();
    }
}
