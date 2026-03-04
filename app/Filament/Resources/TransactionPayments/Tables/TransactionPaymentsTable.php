<?php

namespace App\Filament\Resources\TransactionPayments\Tables;

use App\Enums\Month;
use App\Enums\TransactionPaymentType;
use App\Enums\TransactionRecurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Filament\Inputs\CurrencyInput;
use App\Helpers\BillableHelper;
use App\Models\BankAccount;
use App\Models\CreditCard;
use App\Models\TransactionPayment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\FusedGroup;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

class TransactionPaymentsTable
{

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                /** Nome */
                TextColumn::make('transaction.name')
                    ->suffix(function (TransactionPayment $record) {
                        if ($record->transaction->payments_count > 1 && $record->transaction->payment_type !== TransactionPaymentType::RECURRENT) {
                            return " - ($record->payment_number/{$record->transaction->payments_count})";
                        }

                        return null;
                    })
                    ->label('Nome')
                    ->color('primary'),

                /** Categoria */
                TextColumn::make('transaction.category.name')
                    ->label('Categoria')
                    ->placeholder("N/A")
                    ->searchable(),

                /** Carteira */
                TextColumn::make('billable.name')
                    ->label('Carteira')
                    ->default('N/A')
                    ->icon(fn(TransactionPayment $record) => match ($record->billable ? get_class($record->billable) : null) {
                        BankAccount::class => Heroicon::BuildingOffice,
                        CreditCard::class => Heroicon::CreditCard,
                        default => null,
                    })
                    ->badge()
                    ->color(fn(TransactionPayment $record) => Color::hex(data_get($record, 'billable.color', 'secondary')))
                    ->searchable(),

                /** Cobrança */
                TextColumn::make('billing_date')
                    ->label('Data da cobrança')
                    ->width(1)
                    ->date('d/m/Y')
                    ->icon(fn(TransactionPayment $record) => $record->transaction->payment_type === TransactionPaymentType::RECURRENT ? Heroicon::ArrowPath : null)
                    ->iconColor(Color::Blue)
                    ->iconPosition('after')
                    ->tooltip(function (TransactionPayment $record) {

                        $recurringDay = data_get($record, 'transaction.recurring_day');
                        $recurringMonth = data_get($record, 'transaction.recurring_month');

                        return match (data_get($record, 'transaction.recurrency_type')) {
                            TransactionRecurrencyType::MONTHLY => "Cobrado todo dia {$recurringDay}",
                            TransactionRecurrencyType::YEARLY => "Cobrado todo dia {$recurringDay} do mês de {$recurringMonth}",
                            default => null
                        };

                    })
                    ->sortable(),

                /** Cobrança */
                TextColumn::make('payment_date')
                    ->label('Pago em')
                    ->width(1)
                    ->date('d/m/Y')
                    ->placeholder("Pendente")
                    ->sortable(),

                /** Status */
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(PaymentStatus $state) => match ($state) {
                        PaymentStatus::PENDING => 'warning',
                        PaymentStatus::PAID => 'success',
                    }),

                /** Valor */
                TextColumn::make('amount')
                    ->label('Valor')
                    ->prefix('R$')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->summarize([self::amountSummarizer("amount", "Valor")])
                    ->color(fn(TransactionPayment $record) => $record->transaction->transaction_type === TransactionType::EXPENSE ? 'danger' : 'success'),

                /** Valor Pago */
                TextColumn::make('paid_amount')
                    ->label('Valor pago')
                    ->placeholder("R$ 0,00")
                    ->prefix('R$')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->summarize([self::amountSummarizer("paid_amount", "Valor Pago")])
                    ->color(fn(TransactionPayment $record) => $record->transaction->transaction_type === TransactionType::EXPENSE ? 'danger' : 'success'),
            ])
            ->filters([
                Filter::make('billing_month_year')
                    ->schema([
                        FusedGroup::make([
                            Select::make('billing_month')
                                ->placeholder("Mês")
                                ->searchable()
                                ->preload()
                                ->default(now()->month)
                                ->placeholder("Mês")
                                ->required()
                                ->selectablePlaceholder(false)
                                ->options(Month::class),
                            TextInput::make('billing_year')
                                ->placeholder("Ano")
                                ->default(now()->year)
                                ->required()
                                ->placeholder("Ano")
                                ->numeric()
                        ])
                            ->label("Mês / Ano")
                            ->columns()
                    ])
                    ->indicateUsing(function (array $data) {

                        [$month, $year] = array_values($data);

                        return Indicator::make($month && $year ? Month::from($month)->getLabel() . ' ' . $year : null)
                            ->removable(false);

                    })
                    ->modifyBaseQueryUsing(function (EloquentBuilder $query, array $data) {

                        [$month, $year] = array_values($data);

                        $month && $year && $query->filterBillingYearMonth($year, $month);

                    }),
                SelectFilter::make('transaction.transaction_type')
                    ->label("Tipo de pagamento")
                    ->searchable()
                    ->options(TransactionType::class)
                    ->preload(),
                SelectFilter::make('transaction.category')
                    ->label("Categoria")
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->relationship('transaction.category', 'name'),
                SelectFilter::make('status')
                    ->native(false)
                    ->options(PaymentStatus::class),
            ])
            ->filtersFormWidth(Width::Medium)
            ->defaultSort(function (EloquentBuilder $query) {
                $query->joinRelation('transaction')
                    ->orderBy('transactions.transaction_type')
                    ->orderBy('amount', 'desc');
            })
            ->groups([
                Group::make('transaction.transaction_type')
                    ->label("Tipo de transação")
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn(TransactionPayment $record) => str($record->transaction->transaction_type->getLabel())->plural())
                    ->titlePrefixedWithLabel(false),
                Group::make('billable_type')
                    ->label("Carteira")
                    ->scopeQueryByKeyUsing(fn(EloquentBuilder $query, string $key) => $query->where('transaction_payments.billable_type', $key))
                    ->getTitleFromRecordUsing(function (TransactionPayment $record) {
                        if (!$record->billable) {
                            return 'N/A';
                        }

                        $type = match (get_class($record->billable)) {
                            BankAccount::class => 'Conta bancária',
                            CreditCard::class => 'Cartão de crédito',
                            default => 'Carteira',
                        };

                        return "$type - {$record->billable->name}";
                    })
                    ->collapsible()
            ])
            ->recordActions([

                /** Estornar pagamento */
                Action::make('reverse-payment')
                    ->requiresConfirmation()
                    ->label("Estornar")
                    ->visible(fn(TransactionPayment $record) => $record->isPaid())
                    ->icon(Heroicon::ArrowUturnLeft)
                    ->color(Color::Orange)
                    ->successNotificationTitle("Pagamento estornado com sucesso!")
                    ->action(fn(TransactionPayment $record) => $record->markAsPending()),

                /** Confirmar pagamento */
                Action::make('confirm-payment')
                    ->modal()
                    ->label("Confirmar")
                    ->visible(fn(TransactionPayment $record) => $record->isPending())
                    ->color(Color::Green)
                    ->successNotificationTitle("Pagamento confirmado com sucesso!")
                    ->icon(Heroicon::CheckCircle)
                    ->fillForm(fn(TransactionPayment $record) => [
                        'paid_amount' => $record->amount,
                        'payment_date' => now(),
                        'billable_type' => $record->billable_type,
                        'billable_id' => $record->billable_id,
                    ])
                    ->schema([

                        // Cobrado em
                        MorphToSelect::make('billable')
                            ->label("Cobrado em")
                            ->native(false)
                            ->types(
                                array_map(fn($type) => MorphToSelect\Type::make($type)
                                    ->label(match ($type) {
                                        CreditCard::class => "Cartão de crédito",
                                        BankAccount::class => "Conta Bancária (PIX / Transferência)"
                                    })
                                    ->titleAttribute('name')
                                    ->getOptionLabelFromRecordUsing(BillableHelper::getBillableOptionLabel(...)),
                                    [CreditCard::class, BankAccount::class])
                            )
                            ->modifyKeySelectUsing(fn(Select $select): Select => $select->searchable()->preload()->allowHtml()),

                        /** Data de pagamento */
                        DatePicker::make('payment_date')
                            ->label('Data de pagamento')
                            ->required(),


                        /** Valor Pago */
                        CurrencyInput::make('paid_amount')
                            ->label('Valor pago')
                            ->prefix("R$")
                            ->rules(['required', 'numeric', 'min:0.01']),

                    ])
                    ->action(fn(array $data, TransactionPayment $record) => $record->markAsPaid(
                        paidAmount: $data['paid_amount'],
                        paymentDate: $data['payment_date'],
                        billableType: $data['billable_type'],
                        billableId: $data['billable_id'],
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                    /** Confirmar selecionados */
                    BulkAction::make('bulk-confirm-payment')
                        ->icon(Heroicon::CheckCircle)
                        ->label("Confirmar")
                        ->color(Color::Green)
                        ->successNotificationTitle("Pagamentos confirmados com sucesso!")
                        ->requiresConfirmation()
                        ->modalHeading("Confirmar pagamentos")
                        ->action(fn(Collection $records) => $records->each(fn(TransactionPayment $record) => $record->markAsPaid(
                            billableType: $record->billable_type,
                            billableId: $record->billable_id,
                        ))),

                    /** Estornar selecionados */
                    BulkAction::make('bulk-reverse-payment')
                        ->icon(Heroicon::ArrowUturnLeft)
                        ->label("Estornar")
                        ->color(Color::Orange)
                        ->successNotificationTitle("Pagamentos estornados com sucesso!")
                        ->requiresConfirmation()
                        ->modalHeading("Estornar pagamentos")
                        ->action(fn(Collection $records) => $records->each(fn(TransactionPayment $record) => $record->markAsPending())),
                ]),
            ])
            ->stackedOnMobile();

    }

    public static function amountSummarizer(string $column, string $label)
    {
        return Summarizer::make()
            ->using(function (Builder $query) use ($column) {

                $transactionJoin = $query->leftJoin('transactions', 'transactions.id', '=', 'transaction_payments.transaction_id');

                $revenuesSum = $transactionJoin->clone()
                    ->where('transactions.transaction_type', '=', TransactionType::REVENUE->value)
                    ->sum("transaction_payments.{$column}");

                $expensesSum = $transactionJoin->clone()
                    ->where('transactions.transaction_type', '=', TransactionType::EXPENSE->value)
                    ->sum("transaction_payments.{$column}");

                return $revenuesSum - $expensesSum;

            })
            ->money('BRL')
            ->label($label);

    }

}
