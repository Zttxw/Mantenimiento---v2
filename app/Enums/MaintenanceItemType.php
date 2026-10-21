<?php

namespace App\Enums;

enum MaintenanceItemType: string
{
    case REPUESTO = 'repuesto';
    case INSUMO = 'insumo';
    case SERVICIO = 'servicio';
    case HERRAMIENTA = 'herramienta';

    public function label(): string
    {
        return match ($this) {
            self::REPUESTO => 'Repuesto',
            self::INSUMO => 'Insumo',
            self::SERVICIO => 'Servicio externo',
            self::HERRAMIENTA => 'Herramienta',
        };
    }
}
