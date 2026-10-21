<?php

namespace App\Http\Controllers\Role;

use App\DTOs\Role\RoleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Services\Permission\PermissionService;
use App\Services\Role\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly PermissionService $permissionService,
    ) {}

    public function index(): View
    {
        $roles = $this->roleService->getAll();

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = $this->permissionService->getAll();

        return view('roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roleService->create(
            RoleDTO::fromRequest($request->validated())
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rol creado exitosamente.');
    }

    public function edit(int $id): View
    {
        $role = $this->roleService->findOrFail($id);
        $permissions = $this->permissionService->getAll();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(UpdateRoleRequest $request, int $id): RedirectResponse
    {
        $this->roleService->update(
            $id,
            RoleDTO::fromRequest($request->validated())
        );

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->roleService->delete($id);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Rol eliminado exitosamente.');
    }
}
