<?php

namespace App\Enums;

enum RoleEnum: string
{
    case COORDINADOR_OTI = 'coordinador_oti';
    case JEFE_OTI = 'jefe_oti';
    case TECNICO = 'tecnico';
    case AUXILIAR_TI = 'auxiliar_ti';

    // Constante retrocompatible para tests y seeders previos
    public const ADMIN = self::COORDINADOR_OTI;

    public const PRACTICANTE = self::AUXILIAR_TI;

    public function label(): string
    {
        return match ($this) {
            self::COORDINADOR_OTI => 'Coordinador OTI (Superadmin)',
            self::JEFE_OTI => 'Jefe de OTI',
            self::TECNICO => 'Técnico Especialista',
            self::AUXILIAR_TI => 'Auxiliar de TI',
        };
    }
}
