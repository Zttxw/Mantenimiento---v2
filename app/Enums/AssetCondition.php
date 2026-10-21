<?php

namespace App\Enums;

enum AssetCondition: string
{
    case BUENO = 'bueno';
    case REGULAR = 'regular';
    case MALO = 'malo';
    case OBSOLETO = 'obsoleto';

    public function label(): string
    {
        return match ($this) {
            self::BUENO => 'Bueno',
            self::REGULAR => 'Regular',
            self::MALO => 'Malo',
            self::OBSOLETO => 'Obsoleto',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BUENO => 'green',
            self::REGULAR => 'yellow',
            self::MALO => 'red',
            self::OBSOLETO => 'gray',
        };
    }
}
