<?php

namespace App\Filament\Resources\CreditCards\Tables;

use App\Models\CreditCard;
use App\Models\Transaction;
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
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CreditCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /** Cor */
                ColorColumn::make('color')
                    ->label(''),

                /** Nome */
                TextColumn::make('name')
                    ->label('Nome')
                    ->color('primary')
                    ->searchable(),

                /** Conta bancária */
                TextColumn::make('bankAccount.name')
                    ->label('Conta bancária')
                    ->badge()
                    ->color(fn(CreditCard $record) => Color::hex(data_get($record, 'bankAccount.color', 'secondary')))
                    ->searchable(),

                /** Dia de fechamento da fatura */
                TextColumn::make('billing_cycle_end_date')
                    ->label('Fechamento')
                    ->numeric()
                    ->sortable(),

                /** Dia de vencimento da fatura */
                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->numeric()
                    ->sortable(),


                /** Limite total */
                TextColumn::make('total_limit')
                    ->label('Limite total')
                    ->alignEnd()
                    ->sortable()
                    ->money('BRL')
                    ->summarize([Sum::make()->money('BRL')]),

                /** Limite utilizado */
                TextColumn::make('used_limit')
                    ->label('Limite utilizado')
                    ->alignEnd()
                    ->sortable()
                    ->money('BRL')
                    ->summarize([Sum::make()->money('BRL')]),

                /** Limite disponível */
                TextColumn::make('available_limit')
                    ->label('Limite disponível')
                    ->alignEnd()
                    ->sortable()
                    ->money('BRL')
                    ->summarize([Summarizer::make()
                        ->using(fn($query) => $query->sum('total_limit') - $query->sum('used_limit'))
                        ->money('BRL')
                        ->label("Soma")
                    ]),

                /** Excluído em */
                TextColumn::make('deleted_at')
                    ->label('Excluído em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                /** Conta bancária  */
                SelectFilter::make('bank_account_id')
                    ->label("Conta bancária")
                    ->relationship('bankAccount', 'name')
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
