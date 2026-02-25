<?php

namespace App\Helpers;

class CurrencyHelper
{
    public static function stringToFloat(string|float|null $amount, string $decimalSeparator = ',', string $thousandSeparator = '.'): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $amount = (string)$amount;

        return floatval(str_replace([$thousandSeparator, $decimalSeparator], ['', '.'], $amount));
    }
}
