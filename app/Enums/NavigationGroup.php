<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasLabel, HasIcon
{
    case WALLETS;

    public function getLabel(): string
    {
        return match ($this) {
            self::WALLETS => 'Carteiras',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::WALLETS => 'heroicon-o-wallet',
        };
    }
}
