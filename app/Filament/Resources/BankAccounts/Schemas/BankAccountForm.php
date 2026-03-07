<?php

namespace App\Filament\Resources\BankAccounts\Schemas;

use App\Enums\BankAccountType;
use App\Filament\Inputs\ColorInput;
use App\Filament\Inputs\CurrencyInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->components([
                        /** Nome */
                        TextInput::make('name')
                            ->placeholder("Ex: Itaú Uniclass PF")
                            ->label('Nome')
                            ->required(),

                        /** Saldo */
                        CurrencyInput::make('balance')
                            ->prefix("R$")
                            ->placeholder("0,00")
                            ->label('Saldo')
                            ->rules(['numeric'])
                            ->required(),

                        /** Tipo (Corrente / Poupança) */
                        Radio::make('type')
                            ->label('Tipo')
                            ->options(BankAccountType::class)
                            ->default(BankAccountType::CHECKING)
                            ->columnSpanFull()
                            ->required(),

                        /** Cor */
                        ColorInput::make()->label("Cor de exibição")
                    ])
            ]);
    }
}
