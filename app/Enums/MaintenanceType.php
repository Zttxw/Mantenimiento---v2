<?php

namespace App\Enums;

enum MaintenanceType: string
{
    case PREVENTIVO = 'preventivo';
    case CORRECTIVO = 'correctivo';
    case PREDICTIVO = 'predictivo';
    case EMERGENCIA = 'emergencia';

    public function label(): string
    {
        return match ($this) {
            self::PREVENTIVO => 'Preventivo',
            self::CORRECTIVO => 'Correctivo',
            self::PREDICTIVO => 'Predictivo',
            self::EMERGENCIA => 'Emergencia',
        };
    }
}
