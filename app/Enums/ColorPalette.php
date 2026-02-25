<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ColorPalette: string implements HasLabel
{
    case slate = 'slate';
    case gray = 'gray';
    case zinc = 'zinc';
    case neutral = 'neutral';
    case stone = 'stone';
    case red = 'red';
    case orange = 'orange';
    case amber = 'amber';
    case yellow = 'yellow';
    case lime = 'lime';
    case green = 'green';
    case emerald = 'emerald';
    case teal = 'teal';
    case cyan = 'cyan';
    case sky = 'sky';
    case blue = 'blue';
    case indigo = 'indigo';
    case violet = 'violet';
    case purple = 'purple';
    case fuchsia = 'fuchsia';
    case pink = 'pink';
    case rose = 'rose';

    public function getLabel(): string
    {
        return match ($this) {
            self::slate => 'Ardósia',
            self::gray => 'Cinza',
            self::zinc => 'Zinco',
            self::neutral => 'Neutro',
            self::stone => 'Pedra',
            self::red => 'Vermelho',
            self::orange => 'Laranja',
            self::amber => 'Âmbar',
            self::yellow => 'Amarelo',
            self::lime => 'Lima',
            self::green => 'Verde',
            self::emerald => 'Esmeralda',
            self::teal => 'Azul-petróleo',
            self::cyan => 'Ciano',
            self::sky => 'Céu',
            self::blue => 'Azul',
            self::indigo => 'Índigo',
            self::violet => 'Violeta',
            self::purple => 'Roxo',
            self::fuchsia => 'Fúcsia',
            self::pink => 'Rosa',
            self::rose => 'Rosa-escuro',
        };
    }
}
