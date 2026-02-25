<?php

namespace App\Filament\Resources\BankAccounts\Tables;

use App\Enums\BankAccountType;
use App\Models\BankAccount;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /** Cor */
                ColorColumn::make('color')
                    ->width(1)
                    ->label('')
                    ->width('2em'),

                /** Nome */
                TextColumn::make('name')
                    ->width(1)
                    ->label("Nome")
                    ->color('primary')
                    ->searchable(),

                /** Tipo */
                TextColumn::make('type')
                    ->label("Tipo")
                    ->badge()
                    ->searchable(),

                /** Saldo */
                TextColumn::make('balance')
                    ->label("Saldo")
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->color(fn (BankAccount $record) => match (true) {
                        $record->balance < 0 => Color::Red,
                        $record->balance > 0 => Color::Green,
                        default => 'secondary'
                    })
                    ->money('BRL')
                    ->summarize([Sum::make()->money('BRL')]),

                /** Excluído em */
                TextColumn::make('deleted_at')
                    ->label("Excluído em")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                /** Tipo  */
                SelectFilter::make('type')
                    ->options(BankAccountType::class)
                    ->native(false),

                /** Excluídos */
                TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->stackedOnMobile();
    }
}
