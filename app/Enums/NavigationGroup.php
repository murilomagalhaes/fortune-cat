<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasLabel
{
    case TRANSACTIONS;
    case WALLETS;

    public function getLabel(): string
    {
        return match ($this) {
            self::TRANSACTIONS => 'Transações',
            self::WALLETS => 'Carteiras',
        };
    }
}
