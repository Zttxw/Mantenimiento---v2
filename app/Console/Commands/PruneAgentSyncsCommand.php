<?php

namespace App\Console\Commands;

use App\Enums\AgentSyncType;
use App\Models\Setting;
use App\Services\AgentDevice\AgentDeviceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAgentSyncsCommand extends Command
{
    protected $signature = 'agents:purge-sync-history
                            {--heartbeat-days= : Dias de retencion para heartbeats (default desde settings o 7)}
                            {--snapshot-days= : Dias de retencion para snapshots (default desde settings o 90)}
                            {--compact-snapshots : Compactar snapshots conservando solo el primero de cada tramo estructuralmente identico}
                            {--batch-size=10000 : Tamano del lote para eliminacion}
                            {--database= : Base de datos objetivo (por defecto la configurada)}
                            {--optimize : Ejecutar OPTIMIZE TABLE tras la depuracion (solo MySQL/MariaDB)}
                            {--dry-run : Simular eliminacion sin borrar registros}';

    protected $aliases = ['agent-syncs:prune'];

    protected $description = 'Elimina heartbeats y snapshots antiguos de agent_syncs en lotes configurables, preservando siempre el ultimo snapshot por dispositivo';

    public function __construct(
        private readonly AgentDeviceService $agentDeviceService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetDb = $this->option('database');
        if ($targetDb) {
            config(['database.connections.mysql.database' => $targetDb]);
            DB::purge('mysql');
            DB::reconnect('mysql');
        }

        $currentDb = DB::connection()->getDatabaseName();
        $this->info("Base de datos objetivo: <fg=cyan>{$currentDb}</>");

        // Lectura de retención desde settings con fallbacks
        $heartbeatDays = (int) ($this->option('heartbeat-days') ?? Setting::get('agents.heartbeat_retention_days', 7));
        $snapshotDays = (int) ($this->option('snapshot-days') ?? Setting::get('agents.snapshot_retention_days', 90));
        $compactSnapshots = (bool) $this->option('compact-snapshots');
        $batchSize = max(100, (int) $this->option('batch-size'));
        $dryRun = (bool) $this->option('dry-run');

        $heartbeatCutoff = now()->subDays($heartbeatDays);
        $snapshotCutoff = now()->subDays($snapshotDays);

        $this->info("Retencion Heartbeats: {$heartbeatDays} dias (anteriores a {$heartbeatCutoff->toDateTimeString()})");
        $this->info("Retencion Snapshots: {$snapshotDays} dias (anteriores a {$snapshotCutoff->toDateTimeString()})");
        $this->info("Compactar Snapshots identicos: " . ($compactSnapshots ? '<fg=green>SI</>' : '<fg=yellow>NO</>'));
        $this->info("Tamano de lote: {$batchSize}");

        $statsBefore = $this->getTableStats($currentDb);
        $this->line("Tamano inicial agent_syncs: <fg=yellow>{$statsBefore['size_mb']} MB</> ({$statsBefore['rows']} filas aprox)");

        // 1. Conteo de Heartbeats candidatos
        $pendingHeartbeats = DB::table('agent_syncs')
            ->where('sync_type', AgentSyncType::HEARTBEAT->value)
            ->where('created_at', '<', $heartbeatCutoff)
            ->count();

        // 2. Conteo de Snapshots candidatos PROTEGIENDO el último de cada dispositivo
        $latestSnapshotIds = DB::table('agent_syncs')
            ->where('sync_type', AgentSyncType::SNAPSHOT->value)
            ->selectRaw('MAX(id) as id')
            ->groupBy('agent_device_id')
            ->pluck('id')
            ->filter()
            ->all();
        $latestLookup = array_flip($latestSnapshotIds);

        $snapshotQuery = DB::table('agent_syncs')
            ->where('sync_type', AgentSyncType::SNAPSHOT->value)
            ->where('created_at', '<', $snapshotCutoff);

        if (! empty($latestSnapshotIds)) {
            $snapshotQuery->whereNotIn('id', $latestSnapshotIds);
        }

        $pendingSnapshots = $snapshotQuery->count();
        $retentionSnapshotIdsToExclude = $snapshotQuery->pluck('id')->all();
        $retentionExcludedLookup = array_flip($retentionSnapshotIdsToExclude);

        $this->info("Heartbeats a purgar por retencion: <fg=yellow>{$pendingHeartbeats}</>");
        $this->info("Snapshots a purgar por retencion (> {$snapshotDays}d): <fg=yellow>{$pendingSnapshots}</> (protegiendo " . count($latestSnapshotIds) . " ultimos snapshots)");

        // 3. Conteo de Snapshots a compactar si se solicita
        $duplicateSnapshotIds = [];
        if ($compactSnapshots) {
            $duplicateSnapshotIds = $this->findDuplicateSnapshots($latestLookup, $retentionExcludedLookup);
            $this->info("Snapshots duplicados en tramos identicos a compactar: <fg=yellow>" . count($duplicateSnapshotIds) . "</>");
        }

        if ($dryRun) {
            $totalDryDeleted = $pendingHeartbeats + $pendingSnapshots + count($duplicateSnapshotIds);
            $this->warn("Modo DRY-RUN activo: no se realizo ningun borrado. Total filas candidatas: {$totalDryDeleted}");
            return self::SUCCESS;
        }

        $totalHeartbeatsDeleted = 0;
        $totalSnapshotsDeleted = 0;

        // Purgar heartbeats en lotes
        $this->output->write("Purgando heartbeats... ");
        do {
            $ids = DB::table('agent_syncs')
                ->where('sync_type', AgentSyncType::HEARTBEAT->value)
                ->where('created_at', '<', $heartbeatCutoff)
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                $deleted = DB::table('agent_syncs')->whereIn('id', $ids)->delete();
                $totalHeartbeatsDeleted += $deleted;
                $this->output->write(".");
            }
        } while ($ids->isNotEmpty());
        $this->output->writeln(" <fg=green>Listo ({$totalHeartbeatsDeleted} eliminados)</>");

        // Purgar snapshots en lotes respetando la protección del último snapshot
        $this->output->write("Purgando snapshots... ");
        do {
            $snapQuery = DB::table('agent_syncs')
                ->where('sync_type', AgentSyncType::SNAPSHOT->value)
                ->where('created_at', '<', $snapshotCutoff);

            if (! empty($latestSnapshotIds)) {
                $snapQuery->whereNotIn('id', $latestSnapshotIds);
            }

            $ids = $snapQuery->limit($batchSize)->pluck('id');

            if ($ids->isNotEmpty()) {
                $deleted = DB::table('agent_syncs')->whereIn('id', $ids)->delete();
                $totalSnapshotsDeleted += $deleted;
                $this->output->write(".");
            }
        } while ($ids->isNotEmpty());
        $this->output->writeln(" <fg=green>Listo ({$totalSnapshotsDeleted} eliminados)</>");

        // Compactar snapshots duplicados en lotes si fue solicitado
        $totalCompactedDeleted = 0;
        if ($compactSnapshots && ! empty($duplicateSnapshotIds)) {
            $this->output->write("Compactando snapshots duplicados... ");
            $chunks = array_chunk($duplicateSnapshotIds, $batchSize);
            foreach ($chunks as $chunk) {
                $deleted = DB::table('agent_syncs')->whereIn('id', $chunk)->delete();
                $totalCompactedDeleted += $deleted;
                $this->output->write(".");
            }
            $this->output->writeln(" <fg=green>Listo ({$totalCompactedDeleted} eliminados)</>");
        }

        // OPTIMIZE TABLE condicional (solo MySQL/MariaDB)
        if ($this->option('optimize')) {
            $driver = DB::connection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'])) {
                $this->info("Ejecutando OPTIMIZE TABLE agent_syncs para reclamar espacio en disco...");
                DB::statement('OPTIMIZE TABLE agent_syncs');
                $this->info("Tabla optimizada.");
            } else {
                $this->line("Omitiendo OPTIMIZE TABLE (driver actual: '{$driver}' no es MySQL/MariaDB).");
            }
        }

        $statsAfter = $this->getTableStats($currentDb);
        $reclaimedMb = round($statsBefore['size_mb'] - $statsAfter['size_mb'], 2);

        $this->line("Tamano final agent_syncs: <fg=green>{$statsAfter['size_mb']} MB</>");
        if ($reclaimedMb > 0) {
            $this->line("Espacio recuperado: <fg=bright-green>{$reclaimedMb} MB</>");
        }

        return self::SUCCESS;
    }

    private function findDuplicateSnapshots(array $latestLookup, array $retentionExcludedLookup): array
    {
        $this->output->write("Analizando snapshots de dispositivos para compactar... ");

        $deviceIds = DB::table('agent_syncs')
            ->where('sync_type', AgentSyncType::SNAPSHOT->value)
            ->distinct()
            ->pluck('agent_device_id');

        $duplicateSnapshotIds = [];

        foreach ($deviceIds as $deviceId) {
            $snapshots = DB::table('agent_syncs')
                ->where('agent_device_id', $deviceId)
                ->where('sync_type', AgentSyncType::SNAPSHOT->value)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'payload_json']);

            $currentStreakHash = null;

            foreach ($snapshots as $snap) {
                // Si este snapshot ya fue marcado para purga por retención, lo ignoramos
                if (isset($retentionExcludedLookup[$snap->id])) {
                    continue;
                }

                $payload = is_string($snap->payload_json)
                    ? json_decode($snap->payload_json, true)
                    : (array) $snap->payload_json;

                if (! is_array($payload)) {
                    $currentStreakHash = null;
                    continue;
                }

                $hash = $this->agentDeviceService->computeStructuralSnapshotHash($payload);

                if ($currentStreakHash === null) {
                    // Primer snapshot del tramo: se conserva siempre
                    $currentStreakHash = $hash;
                } elseif ($hash === $currentStreakHash) {
                    // Mismo hash estructural que el anterior en este tramo:
                    // Nunca borrar el último snapshot del dispositivo
                    if (isset($latestLookup[$snap->id])) {
                        continue;
                    }
                    $duplicateSnapshotIds[] = $snap->id;
                } else {
                    // Cambio estructural detectado: inicia un nuevo tramo
                    $currentStreakHash = $hash;
                }
            }
        }

        $this->output->writeln(" <fg=green>Listo</>");

        return $duplicateSnapshotIds;
    }

    private function getTableStats(string $dbName): array
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'])) {
            $count = DB::table('agent_syncs')->count();
            return ['size_mb' => 0.0, 'rows' => $count];
        }

        $result = DB::selectOne("
            SELECT 
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                table_rows AS rows_count
            FROM information_schema.TABLES 
            WHERE table_schema = ? AND table_name = 'agent_syncs'
        ", [$dbName]);

        return [
            'size_mb' => $result ? (float) $result->size_mb : 0.0,
            'rows'    => $result ? (int) $result->rows_count : 0,
        ];
    }
}
