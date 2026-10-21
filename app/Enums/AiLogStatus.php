<?php

namespace App\Enums;

enum AiLogStatus: string
{
    case PENDIENTE = 'pendiente';
    case EXITOSO = 'exitoso';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EXITOSO => 'Exitoso',
            self::ERROR => 'Error',
        };
    }
}
