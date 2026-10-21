<?php

namespace App\Enums;

enum AgentSyncStatus: string
{
    case RECIBIDO = 'recibido';
    case PROCESADO = 'procesado';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::RECIBIDO => 'Recibido',
            self::PROCESADO => 'Procesado',
            self::ERROR => 'Error',
        };
    }
}
