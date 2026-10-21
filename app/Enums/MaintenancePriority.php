<?php

namespace App\Enums;

enum MaintenancePriority: string
{
    case BAJA = 'baja';
    case MEDIA = 'media';
    case ALTA = 'alta';
    case CRITICA = 'critica';

    public function label(): string
    {
        return match ($this) {
            self::BAJA => 'Baja',
            self::MEDIA => 'Media',
            self::ALTA => 'Alta',
            self::CRITICA => 'Crítica',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BAJA => 'gray',
            self::MEDIA => 'yellow',
            self::ALTA => 'orange',
            self::CRITICA => 'red',
        };
    }
}
