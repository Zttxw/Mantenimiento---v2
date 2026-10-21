<?php

namespace App\Enums;

enum MaintenanceCaseStatus: string
{
    case PENDIENTE = 'pendiente';
    case EN_PROGRESO = 'en_progreso';
    case EN_ESPERA = 'en_espera';
    case COMPLETADO = 'completado';
    case CANCELADO = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::EN_PROGRESO => 'En progreso',
            self::EN_ESPERA => 'En espera',
            self::COMPLETADO => 'Completado',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDIENTE => 'gray',
            self::EN_PROGRESO => 'blue',
            self::EN_ESPERA => 'yellow',
            self::COMPLETADO => 'green',
            self::CANCELADO => 'red',
        };
    }
}
