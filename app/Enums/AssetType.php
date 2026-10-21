<?php

namespace App\Enums;

enum AssetType: string
{
    case COMPUTADORA = 'computadora';
    case LAPTOP = 'laptop';
    case SERVIDOR = 'servidor';
    case IMPRESORA = 'impresora';
    case ESCANER = 'escaner';
    case MONITOR = 'monitor';
    case TABLET = 'tablet';
    case TELEFONO_IP = 'telefono_ip';
    case PROYECTOR = 'proyector';
    case UPS = 'ups';
    case SWITCH = 'switch';
    case ROUTER = 'router';
    case ACCESS_POINT = 'access_point';
    case CAMARA = 'camara';
    case DISCO_EXTERNO = 'disco_externo';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::COMPUTADORA => 'Computadora de escritorio',
            self::LAPTOP => 'Laptop',
            self::SERVIDOR => 'Servidor',
            self::IMPRESORA => 'Impresora',
            self::ESCANER => 'Escáner',
            self::MONITOR => 'Monitor',
            self::TABLET => 'Tablet',
            self::TELEFONO_IP => 'Teléfono IP',
            self::PROYECTOR => 'Proyector',
            self::UPS => 'UPS / Regulador',
            self::SWITCH => 'Switch de red',
            self::ROUTER => 'Router',
            self::ACCESS_POINT => 'Punto de acceso Wi-Fi',
            self::CAMARA => 'Cámara de seguridad',
            self::DISCO_EXTERNO => 'Disco externo / USB',
            self::OTRO => 'Otro',
        };
    }
}
