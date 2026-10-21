<?php

namespace App\Enums;

enum AgentDeviceStatus: string
{
    case ACTIVO = 'activo';
    case INACTIVO = 'inactivo';
    case DESCONECTADO = 'desconectado';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::INACTIVO => 'Inactivo',
            self::DESCONECTADO => 'Desconectado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVO => 'green',
            self::INACTIVO => 'gray',
            self::DESCONECTADO => 'red',
        };
    }
}
