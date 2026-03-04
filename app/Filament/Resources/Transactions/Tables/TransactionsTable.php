<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\PaymentStatus;
use App\Enums\TransactionRecurrencyType;
use App\Enums\TransactionType;
use App\Models\BankAccount;
use App\Models\CreditCard;
use App\Models\Transaction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /** Nome */
                TextColumn::make('name')
                    ->color('primary')
                    ->label("Nome")
                    ->searchable(),

                /** Categoria */
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->placeholder("N/A")
                    ->searchable(),

                /** Carteira */
                TextColumn::make('billable.name')
                    ->label('Carteira')
                    ->default('N/A')
                    ->icon(fn (Transaction $record) => match ($record->billable ? get_class($record->billable) : null) {
                        BankAccount::class => Heroicon::BuildingOffice,
                        CreditCard::class => Heroicon::CreditCard,
                        default => null,
                    })
                    ->badge()
                    ->color(fn (Transaction $record) => Color::hex(data_get($record, 'billable.color', 'secondary')))
                    ->searchable(),

                /** Data da transação  */
                TextColumn::make('transaction_date')
                    ->label('Data da transação')
                    ->date('d/m/Y')
                    ->sortable(),

                /** Recorrência */
                TextColumn::make('payments_count')
                    ->label('Pagamentos')
                    ->placeholder('N/A')
                    ->sortable()
                    ->formatStateUsing(function (Transaction $record) {

                        $recurringDay = data_get($record, 'recurring_day');
                        $recurringMonth = data_get($record, 'recurring_month');

                        return match (data_get($record, 'recurrency_type')) {
                            TransactionRecurrencyType::MONTHLY => "Todo dia {$recurringDay}",
                            TransactionRecurrencyType::YEARLY => "Todo dia {$recurringDay} do mês de {$recurringMonth}",
                            default => $record->payments_count
                        };

                    }),

                /** Valor */
                TextColumn::make('total_amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->alignEnd()
                    ->color(fn (Transaction $record) => match ($record->transaction_type) {
                        TransactionType::EXPENSE => Color::Red,
                        TransactionType::REVENUE => Color::Green,
                    })
                    ->sortable(),

                /** Valor */
                TextColumn::make('payments_sum_paid_amount')
                    ->sum([
                        'payments' => fn ($query) => $query->where('status', '=', PaymentStatus::PAID),
                    ], 'paid_amount')
                    ->label('Valor pago')
                    ->money('BRL')
                    ->placeholder('R$ 0,00')
                    ->alignEnd()
                    ->color(fn (Transaction $record) => match ($record->transaction_type) {
                        TransactionType::EXPENSE => Color::Red,
                        TransactionType::REVENUE => Color::Green,
                    })
                    ->sortable(),

            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->groups([
                Group::make('transaction_type')
                    ->label('Tipo de transação')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn (Transaction $record) => str($record->transaction_type->getLabel())->plural())
                    ->titlePrefixedWithLabel(false),
                Group::make('billable_type')
                    ->label('Carteira')
                    ->getTitleFromRecordUsing(function (Transaction $record) {
                        if (! $record->billable) {
                            return 'N/A';
                        }

                        $type = match (get_class($record->billable)) {
                            BankAccount::class => 'Conta bancária',
                            CreditCard::class => 'Cartão de crédito',
                            default => 'Carteira',
                        };

                        return "$type - {$record->billable->name}";
                    })
                    ->collapsible(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->stackedOnMobile();
    }
}
