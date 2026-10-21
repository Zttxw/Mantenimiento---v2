<?php

namespace App\Enums;

enum CampaignStatus: string
{
    case PENDIENTE_APROBACION = 'pendiente_aprobacion';
    case PLANIFICADA = 'planificada';
    case EN_CURSO = 'en_curso';
    case PAUSADA = 'pausada';
    case COMPLETADA = 'completada';
    case CANCELADA = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE_APROBACION => 'Pendiente de Aprobación',
            self::PLANIFICADA => 'Planificada',
            self::EN_CURSO => 'En curso',
            self::PAUSADA => 'Pausada',
            self::COMPLETADA => 'Completada',
            self::CANCELADA => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDIENTE_APROBACION => 'amber',
            self::PLANIFICADA => 'gray',
            self::EN_CURSO => 'blue',
            self::PAUSADA => 'yellow',
            self::COMPLETADA => 'green',
            self::CANCELADA => 'red',
        };
    }
}
