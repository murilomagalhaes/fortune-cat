<?php

namespace App\Helpers;

use App\Enums\ColorPalette;
use Filament\Support\Colors\Color;

class ColorHelper
{
    public static function getColorOptions(): array
    {
        return array_map(function (array $colorGroup) {

            $colors = array_values($colorGroup);

            return array_map(
                fn(string $color) => "<div style='background-color: $color' class='color-option-label'></div>",
                array_combine($colors, $colors)
            );

        }, Color::all());

    }

    public static function getColorOptionsFromPalette(ColorPalette|string|null $palette): array
    {
        if (!$palette) {
            return [];
        }

        if (!is_string($palette)) {
            $palette = $palette->value;
        }

        if (!in_array($palette, array_keys(Color::all()))) {
            return [];
        };

        return self::getColorOptions()[$palette];

    }


}
