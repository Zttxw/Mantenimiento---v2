<?php

namespace App\Enums;

enum AssetStatus: string
{
    case ACTIVO = 'activo';
    case EN_USO = 'en_uso';
    case EN_ALMACEN = 'en_almacen';
    case EN_REPARACION = 'en_reparacion';
    case DADO_DE_BAJA = 'dado_de_baja';
    case EXTRAVIADO = 'extraviado';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::EN_USO => 'En uso',
            self::EN_ALMACEN => 'En almacén',
            self::EN_REPARACION => 'En reparación',
            self::DADO_DE_BAJA => 'Dado de baja',
            self::EXTRAVIADO => 'Extraviado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVO => 'green',
            self::EN_USO => 'blue',
            self::EN_ALMACEN => 'gray',
            self::EN_REPARACION => 'yellow',
            self::DADO_DE_BAJA => 'red',
            self::EXTRAVIADO => 'red',
        };
    }
}
