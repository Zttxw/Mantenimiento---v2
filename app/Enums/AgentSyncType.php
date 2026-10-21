<?php

namespace App\Enums;

enum AgentSyncType: string
{
    case HEARTBEAT = 'heartbeat';
    case SNAPSHOT = 'snapshot';
    case DELTA = 'delta';

    public function label(): string
    {
        return match ($this) {
            self::HEARTBEAT => 'Heartbeat',
            self::SNAPSHOT => 'Snapshot completo',
            self::DELTA => 'Cambios detectados',
        };
    }
}
