<?php

use App\Enums\AgentSyncType;
use App\Models\AgentSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mantenimiento nocturno automático: depuración de heartbeats rutinarios y compactación de snapshots idénticos.
// Preserva automáticamente incidentes de salud críticos y el inventario vigente de cada equipo.
Schedule::command('agents:purge-sync-history --compact-snapshots --batch-size=5000 --sleep-ms=50')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/agents-purge.log'));
