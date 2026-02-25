<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum BankAccountType: string implements HasLabel
{
    case CHECKING = 'CHECKING';
    case SAVINGS = 'SAVINGS';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::CHECKING => 'Corrente',
            self::SAVINGS => 'Poupança',
        };
    }
}
