<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionRecurrencyType: string implements HasLabel
{
    case MONTHLY = 'MONTHLY';
    case YEARLY = 'YEARLY';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::MONTHLY => 'Mensal',
            self::YEARLY => 'Anual',
        };
    }
}
