<?php

namespace App\Filament\Inputs;

use App\Enums\ColorPalette;
use App\Helpers\ColorHelper;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;

class ColorInput
{

    public static function make(?string $paletteKey = 'color_palette', ?string $colorKey = 'color'): FusedGroup
    {
        /** Cor */
        return FusedGroup::make([

            /** Palette */
            Select::make($paletteKey)
                ->placeholder("Paleta")
                ->searchable()
                ->columnSpan(3)
                ->afterStateUpdatedJs(<<<JS
                    if(!\$get('$paletteKey')) {
                        \$set('$colorKey', null)
                    }
                JS)
                ->options(ColorPalette::class),

            /** Color */
            Select::make($colorKey)
                ->noOptionsMessage("Antes, escolha uma paleta de cores")
                ->placeholder("Cor")
                ->native(false)
                ->requiredWith($paletteKey)
                ->allowHtml()
                ->options(fn(Get $get) => ColorHelper::getColorOptionsFromPalette($get($paletteKey))),

        ])
            ->columns(4);
    }
}
