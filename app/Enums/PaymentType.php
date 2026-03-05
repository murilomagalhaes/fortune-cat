<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PaymentType: string implements HasLabel
{
    case SINGLE = 'SINGLE';
    case INSTALLMENTS = 'INSTALLMENTS';
    case RECURRENT = 'RECURRENT';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::SINGLE => 'Único',
            self::INSTALLMENTS => 'Parcelado',
            self::RECURRENT => 'Recorrente',
        };
    }
}
