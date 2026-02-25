<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TransactionType: string implements HasLabel
{
    case EXPENSE = 'EXPENSE';
    case REVENUE = 'REVENUE';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::EXPENSE => 'Despesa',
            self::REVENUE => 'Receita',
        };
    }
}
