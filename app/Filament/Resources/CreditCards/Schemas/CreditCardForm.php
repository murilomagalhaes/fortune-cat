<?php

namespace App\Filament\Resources\CreditCards\Schemas;

use App\Filament\Inputs\ColorInput;
use App\Filament\Inputs\CurrencyInput;
use App\Helpers\BillableHelper;
use App\Helpers\CurrencyHelper;
use App\Models\BankAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CreditCardForm
{
    private static function updateAvailableLimit(Get $get, Set $set): void
    {

        $totalLimit = CurrencyHelper::stringToFloat($get('total_limit') ?: '0,00');
        $usedLimit = CurrencyHelper::stringToFloat($get('used_limit') ?: '0,00');

        $set('available_limit', $totalLimit - $usedLimit);

    }

    private static function updateColor(mixed $state, Get $get, Set $set): void
    {
        if (!$state || $get('color')) {
            return;
        };

        $bankAccount = BankAccount::find($state);

        $set('color_palette', $bankAccount->color_palette);
        $set('color', $bankAccount->color);

    }

    public static function configure(Schema $schema): Schema
    {

        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->components([
                        /** Nome */
                        TextInput::make('name')
                            ->label('Nome')
                            ->placeholder("Ex: Nubank Ultravioleta")
                            ->required(),

                        /** Limites (Total/Utilizado) */
                        Grid::make()
                            ->schema([
                                CurrencyInput::make('total_limit')
                                    ->label('Limite total')
                                    ->prefix("R$")
                                    ->required()
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(self::updateAvailableLimit(...)),
                                CurrencyInput::make('used_limit')
                                    ->label('Limite utilizado')
                                    ->prefix("R$")
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(self::updateAvailableLimit(...)),
                            ]),

                        /** Limite disponivel */
                        TextEntry::make('available_limit')
                            ->label("Limite disponível")
                            ->badge()
                            ->money('BRL')
                            ->default(0),

                        /** Vencimentos */
                        Grid::make()
                            ->schema([
                                TextInput::make('billing_cycle_end_date')
                                    ->label('Dia de fechamento')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->required(),
                                TextInput::make('due_date')
                                    ->label('Dia de vencimento')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->required(),
                            ]),

                        /** Banco */
                        Select::make('bank_account_id')
                            ->label('Conta bancária')
                            ->relationship('bankAccount', 'name')
                            ->allowHtml()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(BillableHelper::getBillableOptionLabel(...))
                            ->live()
                            ->afterStateUpdated(self::updateColor(...)),

                        /** Cor */
                        ColorInput::make()->label("Cor de exibição")
                    ])
            ]);
    }
}
