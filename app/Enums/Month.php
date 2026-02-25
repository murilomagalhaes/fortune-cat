<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum Month: int implements HasLabel
{
    case JANUARY = 1;
    case FEBRUARY = 2;
    case MARCH = 3;
    case APRIL = 4;
    case MAY = 5;
    case JUNE = 6;
    case JULY = 7;
    case AUGUST = 8;
    case SEPTEMBER = 9;
    case OCTOBER = 10;
    case NOVEMBER = 11;
    case DECEMBER = 12;

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::JANUARY => 'Janeiro',
            self::FEBRUARY => 'Fevereiro',
            self::MARCH => 'Março',
            self::APRIL => 'Abril',
            self::MAY => 'Maio',
            self::JUNE => 'Junho',
            self::JULY => 'Julho',
            self::AUGUST => 'Agosto',
            self::SEPTEMBER => 'Setembro',
            self::OCTOBER => 'Outubro',
            self::NOVEMBER => 'Novembro',
            self::DECEMBER => 'Dezembro',
        };
    }
}
