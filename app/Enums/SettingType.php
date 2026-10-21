<?php

namespace App\Enums;

enum SettingType: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
    case TEXT = 'text';

    public function label(): string
    {
        return match ($this) {
            self::STRING => 'Texto',
            self::INTEGER => 'Número',
            self::BOOLEAN => 'Booleano',
            self::JSON => 'JSON',
            self::TEXT => 'Texto largo',
        };
    }
}
