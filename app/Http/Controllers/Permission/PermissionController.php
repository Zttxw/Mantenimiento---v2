<?php

namespace App\Http\Controllers\Permission;

use App\DTOs\Permission\PermissionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Services\Permission\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {}

    public function index(): View
    {
        $permissions = $this->permissionService->getAll();

        return view('permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        return view('permissions.create');
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $this->permissionService->create(
            PermissionDTO::fromRequest($request->validated())
        );

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso creado exitosamente.');
    }

    public function edit(int $id): View
    {
        $permission = $this->permissionService->findOrFail($id);

        return view('permissions.edit', compact('permission'));
    }

    public function update(UpdatePermissionRequest $request, int $id): RedirectResponse
    {
        $this->permissionService->update(
            $id,
            PermissionDTO::fromRequest($request->validated())
        );

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso actualizado exitosamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->permissionService->delete($id);

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso eliminado exitosamente.');
    }
}
