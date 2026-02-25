<?php

namespace App\Rules;

use App\Helpers\CurrencyHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

readonly class CurrencyRule implements ValidationRule
{
    public function __construct(private ?float $min = null, private ?float $max = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $floatValue = CurrencyHelper::stringToFloat($value);

        if ($this->min !== null && $floatValue <= $this->min) {
            $fail("O :attribute deve ser maior que " . \Number::currency($this->min, 'BRL', 'pt_BR'));
        }

        if ($this->max !== null && $floatValue >= $this->max) {
            $fail("O :attribute deve ser menor que " . \Number::currency($this->max, 'BRL', 'pt_BR'));
        }
    }
}
