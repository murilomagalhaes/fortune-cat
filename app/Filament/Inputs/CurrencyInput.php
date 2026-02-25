<?php

namespace App\Filament\Inputs;

use Filament\Forms\Components\TextInput;
use App\Helpers\CurrencyHelper;
use Filament\Support\RawJs;

class CurrencyInput
{
    public static function make(?string $name, string $decimalSeparator = ',', string $thousandsSeparator = '.'): TextInput
    {
        return TextInput::make($name)
            ->mutateStateForValidationUsing(fn(?string $state) => CurrencyHelper::stringToFloat($state, $decimalSeparator, $thousandsSeparator))
            ->dehydrateStateUsing(fn(?string $state) => CurrencyHelper::stringToFloat($state, $decimalSeparator, $thousandsSeparator))
            ->formatStateUsing(fn(?string $state) => number_format($state, 2, $decimalSeparator, $thousandsSeparator))
            ->mask(RawJs::make("\$money(\$input, '$decimalSeparator', '$thousandsSeparator')"));
    }
}
