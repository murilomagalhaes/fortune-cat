<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasLabel
{
    case WALLETS;
    case TRANSACTIONS;

    public function getLabel(): string
    {
        return match ($this) {
            self::WALLETS => 'Carteiras',
            self::TRANSACTIONS => 'Transações',
        };
    }
}
