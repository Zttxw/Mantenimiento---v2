<?php

namespace App\Http\Controllers;

use App\DTOs\User\CreateUserDTO;
use App\DTOs\User\UpdateUserDTO;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\Role\RoleService;
use App\Services\User\UserService;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RoleService $roleService
    ) {}

    public function index()
    {
        $this->authorize('user.view');

        $users = $this->userService->getPaginated(request()->only([
            'search', 'role', 'status',
        ]));

        $roles = $this->roleService->getAll();

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $this->authorize('user.create');

        $roles = $this->roleService->getAll();

        $employees = Employee::active()
            ->whereDoesntHave('user')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'dni', 'email']);

        return view('users.create', compact('roles', 'employees'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->create(
            CreateUserDTO::fromArray($request->validated())
        );

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado correctamente');
    }

    public function edit(User $user)
    {
        $this->authorize('user.edit');

        $roles = $this->roleService->getAll();

        $currentRole = $user->roles()->first()?->name;

        $employees = Employee::active()
            ->where(fn ($q) => $q->whereDoesntHave('user')->orWhere('id', $user->employee_id))
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'dni', 'email']);

        return view('users.edit', compact('user', 'roles', 'currentRole', 'employees'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->update(
            $user,
            UpdateUserDTO::fromArray($request->validated())
        );

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado correctamente');
    }

    public function toggleActive(User $user)
    {
        $this->authorize('user.edit');

        $this->userService->toggleActive($user);

        return back()->with('success', 'Estado del usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        $this->authorize('user.delete');

        $this->userService->delete($user);

        return back()->with('success', 'Usuario eliminado correctamente');
    }
}
