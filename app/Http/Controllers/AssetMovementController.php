<?php

namespace App\Http\Controllers;

use App\DTOs\AssetMovement\CreateAssetMovementDTO;
use App\Enums\MovementType;
use App\Http\Requests\AssetMovement\StoreAssetMovementRequest;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\OrganizationalUnit;
use App\Services\AssetMovement\AssetMovementService;

class AssetMovementController extends Controller
{
    public function __construct(
        private readonly AssetMovementService $movementService,
    ) {}

    public function index()
    {
        $this->authorize('asset-movement.view');

        $movements = $this->movementService->getPaginated(request()->only(['asset_id', 'type']));

        $types = MovementType::cases();

        return view('asset-movements.index', compact('movements', 'types'));
    }

    public function create()
    {
        $this->authorize('asset-movement.create');

        $assets = Asset::orderBy('name')->get();
        $units = OrganizationalUnit::where('is_active', true)->orderBy('name')->get();
        $employees = Employee::where('is_active', true)->orderBy('full_name')->get();
        $types = MovementType::cases();

        return view('asset-movements.create', compact('assets', 'units', 'employees', 'types'));
    }

    public function store(StoreAssetMovementRequest $request)
    {
        $data = array_merge($request->validated(), [
            'created_by' => auth()->id(),
        ]);

        $this->movementService->create(
            CreateAssetMovementDTO::fromArray($data)
        );

        return redirect()->route('asset-movements.index')
            ->with('success', 'Movimiento registrado correctamente.');
    }
}
