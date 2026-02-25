<?php

namespace App\Helpers;

use App\Models\BankAccount;
use App\Models\CreditCard;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;

class BillableHelper
{
    public static function getBillableOptionLabel(BankAccount|CreditCard $record): string
    {
        return view('filament::components.badge', [
            'slot' => $record->name,
            'icon' => match (get_class($record)) {
                BankAccount::class => Heroicon::BuildingOffice,
                CreditCard::class => Heroicon::CreditCard,
            },
            'color' => Color::hex(data_get($record, 'color', 'secondary')),
        ])->toHtml();
    }
}
