<?php

namespace App\Enums;

enum CampaignAssetStatus: string
{
    case PENDIENTE = 'pendiente';
    case PROGRAMADO = 'programado';
    case ATENDIDO = 'atendido';
    case OMITIDO = 'omitido';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::PROGRAMADO => 'Programado',
            self::ATENDIDO => 'Atendido',
            self::OMITIDO => 'Omitido',
        };
    }
}
