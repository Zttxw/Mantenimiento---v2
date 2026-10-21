<?php

namespace App\Enums;

enum MovementType: string
{
    case ASIGNACION = 'asignacion';
    case TRASLADO = 'traslado';
    case DEVOLUCION = 'devolucion';
    case BAJA = 'baja';
    case INGRESO = 'ingreso';
    case PRESTAMO = 'prestamo';

    public function label(): string
    {
        return match ($this) {
            self::ASIGNACION => 'Asignación',
            self::TRASLADO => 'Traslado',
            self::DEVOLUCION => 'Devolución',
            self::BAJA => 'Baja',
            self::INGRESO => 'Ingreso',
            self::PRESTAMO => 'Préstamo',
        };
    }
}
