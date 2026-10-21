<?php

namespace App\Services\Technician;

use App\Actions\MaintenanceCase\CloseCaseAction;
use App\Actions\MaintenanceCase\CreateCaseAction;
use App\DTOs\MaintenanceCase\CreateMaintenanceCaseDTO;
use App\Enums\AgentDeviceStatus;
use App\Enums\MaintenanceCaseStatus;
use App\Models\AgentDevice;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\MaintenanceCampaign;
use App\Models\MaintenanceCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TechnicianService
{
    public function __construct(
        private readonly CreateCaseAction $createCaseAction,
        private readonly CloseCaseAction $closeCaseAction,
    ) {}

    public function getDashboardData(?Employee $employee): array
    {
        $myStats = ['pending' => 0, 'in_progress' => 0, 'waiting' => 0, 'done_today' => 0];
        $myCases = collect();

        if ($employee) {
            $myStats = [
                'pending' => MaintenanceCase::where('assigned_technician_id', $employee->id)
                    ->whereNull('campaign_id')
                    ->where('status', MaintenanceCaseStatus::PENDIENTE)->count(),
                'in_progress' => MaintenanceCase::where('assigned_technician_id', $employee->id)
                    ->whereNull('campaign_id')
                    ->where('status', MaintenanceCaseStatus::EN_PROGRESO)->count(),
                'waiting' => MaintenanceCase::where('assigned_technician_id', $employee->id)
                    ->whereNull('campaign_id')
                    ->where('status', MaintenanceCaseStatus::EN_ESPERA)->count(),
                'done_today' => MaintenanceCase::where('assigned_technician_id', $employee->id)
                    ->whereNull('campaign_id')
                    ->where('status', MaintenanceCaseStatus::COMPLETADO)
                    ->whereDate('finished_at', today())->count(),
            ];

            $myCases = MaintenanceCase::with(['asset', 'asset.organizationalUnit'])
                ->where('assigned_technician_id', $employee->id)
                ->whereNull('campaign_id')
                ->whereNotIn('status', [
                    MaintenanceCaseStatus::COMPLETADO->value,
                    MaintenanceCaseStatus::CANCELADO->value,
                ])
                ->orderByRaw("FIELD(status, 'en_progreso', 'pendiente', 'en_espera')")
                ->limit(8)
                ->get();
        }

        $activeCampaigns = MaintenanceCampaign::where('status', 'en_curso')
            ->with('coordinator')
            ->limit(3)
            ->get();

        $offlineAgents = AgentDevice::with('asset')
            ->where(function ($q) {
                $q->where('status', AgentDeviceStatus::DESCONECTADO)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('last_heartbeat_at')
                            ->where('last_heartbeat_at', '<', now()->subMinutes(10));
                    });
            })
            ->limit(5)
            ->get();

        return compact('myStats', 'myCases', 'activeCampaigns', 'offlineAgents');
    }

    public function getWorkQueueData(?Employee $employee): array
    {
        $queues = [
            'pendiente' => collect(),
            'en_progreso' => collect(),
            'en_espera' => collect(),
            'completado' => collect(),
        ];

        if (! $employee) {
            return $queues;
        }

        foreach (['pendiente', 'en_progreso', 'en_espera'] as $status) {
            $queues[$status] = MaintenanceCase::with(['asset'])
                ->where('assigned_technician_id', $employee->id)
                ->whereNull('campaign_id')
                ->where('status', $status)
                ->latest()
                ->get();
        }

        $queues['completado'] = MaintenanceCase::with(['asset'])
            ->where('assigned_technician_id', $employee->id)
            ->whereNull('campaign_id')
            ->where('status', MaintenanceCaseStatus::COMPLETADO)
            ->whereDate('finished_at', today())
            ->latest()
            ->get();

        return $queues;
    }

    public function getAttendAssetFormData(): array
    {
        return [
            'assets' => Asset::orderBy('name')->get(['id', 'name', 'internal_code', 'asset_type', 'status']),
        ];
    }

    public function updateProgress(MaintenanceCase $case, array $data): MaintenanceCase
    {
        if (in_array($case->status->value, ['completado', 'cancelado'])) {
            throw new \RuntimeException('No se puede actualizar un caso ya cerrado.');
        }

        $update = [];

        if (! empty($data['status'])) {
            $update['status'] = $data['status'];
        }
        foreach (['maintenance_type', 'priority', 'problem_description', 'diagnosis', 'actions_taken', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        if (! empty($update)) {
            $case->update($update);
        }

        return $case->fresh();
    }

    public function registerAttention(array $data, User $user): MaintenanceCase
    {
        $employee = $user->employee;

        return DB::transaction(function () use ($data, $employee, $user) {
            $initialStatus = $data['final_status'] === 'en_progreso' ? 'en_progreso' : 'pendiente';

            $dto = CreateMaintenanceCaseDTO::fromArray([
                'asset_id' => $data['asset_id'],
                'maintenance_type' => $data['maintenance_type'],
                'priority' => $data['priority'],
                'problem_description' => $data['problem_description'],
                'diagnosis' => $data['diagnosis'] ?? null,
                'actions_taken' => $data['actions_taken'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $initialStatus,
                'assigned_technician_id' => $employee?->id,
                'reported_by_employee_id' => $employee?->id,
                'created_by' => $user->id,
            ]);

            $case = $this->createCaseAction->execute($dto);

            foreach ($data['items'] ?? [] as $item) {
                $qty = (int) $item['quantity'];
                $cost = (float) ($item['unit_cost'] ?? 0);
                $case->items()->create([
                    'item_type' => $item['item_type'],
                    'name' => $item['name'],
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'total_cost' => $qty * $cost,
                ]);
            }

            if ($data['final_status'] === 'completado') {
                $this->closeCaseAction->execute($case, [
                    'actions_taken' => $data['actions_taken'] ?? $data['diagnosis'] ?? $data['problem_description'],
                ]);
            }

            return $case->fresh();
        });
    }
}
