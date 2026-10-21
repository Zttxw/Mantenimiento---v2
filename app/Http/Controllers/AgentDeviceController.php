<?php

namespace App\Http\Controllers;

use App\DTOs\AgentDevice\HeartbeatDTO;
use App\DTOs\AgentDevice\RegisterAgentDTO;
use App\DTOs\AgentSync\CreateAgentSyncDTO;
use App\Http\Requests\Agent\HeartbeatRequest;
use App\Http\Requests\Agent\RegisterAgentRequest;
use App\Http\Requests\Agent\SyncRequest;
use App\Models\AgentDevice;
use App\Models\Asset;
use App\Enums\AgentSyncType;
use App\Services\AgentDevice\AgentDeviceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgentDeviceController extends Controller
{
    public function __construct(
        private readonly AgentDeviceService $agentDeviceService,
    ) {}

    /**
     * Registro de un nuevo agente (llamado desde el cliente agente con clave de instalación).
     */
    public function register(RegisterAgentRequest $request): JsonResponse
    {
        $assetCode = $request->validated()['asset_internal_code'] ?? null;
        $asset = $assetCode
            ? Asset::query()
                ->where('internal_code', $assetCode)
                ->orWhere('patrimonial_code', $assetCode)
                ->first()
            : null;

        $dto = RegisterAgentDTO::fromArray($request->validated());

        $device = $this->agentDeviceService->register($asset, $dto);
        $apiToken = $device->getAttribute('plain_api_token');

        if ($assetCode && ! $device->asset_id) {
            $device = $this->agentDeviceService->bindAsset(
                $device,
                $assetCode,
                null,
                null,
                $dto->last_snapshot_json,
            );
        }

        return response()->json([
            'message'    => 'Agente registrado correctamente.',
            'uuid'       => $device->uuid,
            'api_token'  => $apiToken,  // Solo se devuelve en el registro
            'agentUuid'  => $device->uuid,
            'apiToken'   => $apiToken,
        ], 201);
    }

    /**
     * Heartbeat periódico del agente.
     */
    public function heartbeat(HeartbeatRequest $request, string $uuid): JsonResponse
    {
        $device = $request->attributes->get('agent_device')
            ?? AgentDevice::where('uuid', $uuid)->firstOrFail();

        $this->agentDeviceService->heartbeat(
            $device,
            HeartbeatDTO::fromArray($request->validated())
        );

        return response()->json(['message' => 'OK']);
    }

    /**
     * Envío de datos de sincronización (snapshot o delta).
     */
    public function sync(SyncRequest $request, string $uuid): JsonResponse
    {
        $device = $request->attributes->get('agent_device')
            ?? AgentDevice::where('uuid', $uuid)->firstOrFail();

        $dto = CreateAgentSyncDTO::fromArray(array_merge(
            $request->validated(),
            ['agent_device_id' => $device->id]
        ));

        $sync = $this->agentDeviceService->receiveSync($device, $dto);

        return response()->json([
            'message' => 'Sincronización recibida.',
            'sync_id' => $sync->id,
        ]);
    }

    public function snapshot(Request $request, string $uuid): JsonResponse
    {
        $device = $request->attributes->get('agent_device')
            ?? AgentDevice::where('uuid', $uuid)->firstOrFail();

        $binding = $request->input('administrativeBinding') ?? $request->input('AdministrativeBinding');
        if (is_array($binding)) {
            $device = $this->agentDeviceService->bindAsset(
                $device,
                $binding['assetCode'] ?? null,
                $binding['organizationalUnitId'] ?? null,
                $binding['responsibleEmployeeId'] ?? null,
                $request->all(),
            );
        }

        $dto = CreateAgentSyncDTO::fromArray([
            'agent_device_id' => $device->id,
            'sync_type' => AgentSyncType::SNAPSHOT->value,
            'payload_json' => $request->all(),
            'detected_changes_json' => null,
        ]);

        $sync = $this->agentDeviceService->receiveSync($device, $dto);

        return response()->json([
            'message' => 'Snapshot recibido.',
            'sync_id' => $sync->id,
        ]);
    }

    public function bindAsset(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'assetCode' => ['required', 'string', 'max:100'],
            'organizationalUnitId' => ['nullable', 'integer', 'exists:organizational_units,id'],
            'responsibleEmployeeId' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $device = $request->attributes->get('agent_device')
            ?? AgentDevice::where('uuid', $uuid)->firstOrFail();

        $device = $this->agentDeviceService->bindAsset(
            $device,
            $validated['assetCode'],
            $validated['organizationalUnitId'] ?? null,
            $validated['responsibleEmployeeId'] ?? null,
        );

        return response()->json([
            'message' => 'Vinculo administrativo recibido.',
            'asset_id' => $device->asset_id,
        ]);
    }

    // ── Web / Admin ──────────────────────────────────────────────

    public function webIndex()
    {
        $this->authorize('agent.view');

        $devices = $this->agentDeviceService->getPaginated(
            request()->only(['status'])
        );

        return view('agents.index', compact('devices'));
    }

    public function webStatus(AgentDevice $agentDevice): JsonResponse
    {
        $this->authorize('agent.view');

        $agentDevice->refresh();

        $heartbeats = $agentDevice->syncs()
            ->where('sync_type', AgentSyncType::HEARTBEAT->value)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at')
            ->get(['id', 'created_at', 'payload_json'])
            ->map(fn ($s) => [
                'ts'      => $s->created_at->timestamp,
                'ip'      => data_get($s->payload_json, 'last_ip'),
                'version' => data_get($s->payload_json, 'agent_version'),
            ])
            ->values()
            ->all();

        return response()->json([
            'last_heartbeat_at' => $agentDevice->last_heartbeat_at?->timestamp,
            'status'            => $agentDevice->status->value,
            'is_online'         => $agentDevice->isOnline(),
            'last_ip'           => $agentDevice->last_ip,
            'agent_version'     => $agentDevice->agent_version,
            'heartbeats'        => $heartbeats,
            'server_ts'         => now()->timestamp,
        ]);
    }

    public function webShow(AgentDevice $agentDevice)
    {
        $this->authorize('agent.view');

        $agentDevice->load('asset');

        $heartbeats = $agentDevice->syncs()
            ->where('sync_type', AgentSyncType::HEARTBEAT->value)
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at')
            ->get(['id', 'created_at', 'payload_json', 'status'])
            ->map(fn ($s) => [
                'ts'      => $s->created_at->timestamp,
                'ip'      => data_get($s->payload_json, 'last_ip'),
                'version' => data_get($s->payload_json, 'agent_version'),
            ])
            ->values()
            ->all();

        $recentSyncs = $agentDevice->syncs()
            ->latest('synced_at')
            ->take(5)
            ->get();

        $totalSyncsCount = $agentDevice->syncs()->count();

        return view('agents.show', compact('agentDevice', 'heartbeats', 'recentSyncs', 'totalSyncsCount'));
    }
}
