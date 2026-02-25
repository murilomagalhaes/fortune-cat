<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::PAID => 'Pago',
        };
    }
}
