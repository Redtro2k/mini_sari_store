<?php

namespace App\Enums;

enum Unit: string
{
    case KG = 'kg';
    case PCS = 'pcs';
    case BOX = 'box';
    case LITER = 'liter';
    case METERS = 'meters';

    public function getLabels(): string
    {
        return match ($this) {
            self::KG => 'Kilogram',
            self::PCS => 'Pcs',
            self::BOX => 'Box',
            self::LITER => 'Liter',
            self::METERS => 'Meters',
        };
    }
}
