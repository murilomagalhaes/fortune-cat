<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\DTO\TransactionDTO;
use App\Enums\Month;
use App\Enums\PaymentStatus;
use App\Enums\TransactionPaymentType;
use App\Enums\TransactionRecurrencyType;
use App\Enums\TransactionType;
use App\Filament\Inputs\CurrencyInput;
use App\Helpers\BillableHelper;
use App\Helpers\CurrencyHelper;
use App\Models\BankAccount;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\TransactionPayment;
use App\Services\TransactionsService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Number;

class TransactionForm
{
    public static function steps(): array
    {
        return [

            /** Informações Básicas */
            self::stepOne(),

            /** Valores */
            self::stepTwo(),

            /** Step: Pagamentos */
            self::stepThree(),
        ];
    }

    public static function stepOne(): Step
    {
        return Step::make('Informações Básicas')
            ->description(function (Get $get): ?string {

                [$type, $name] = [$get->enum('transaction_type', TransactionType::class), $get('name')];

                return $type && $name ? $type->getLabel().': '.$name : null;

            })
            ->schema([
                Section::make()
                    ->components([
                        /** Nome */
                        TextInput::make('name')
                            ->label('Nome')
                            ->placeholder('Ex: Aluguel')
                            ->required(),

                        /** Tipo de Transação */
                        Radio::make('transaction_type')
                            ->label('Tipo')
                            ->inline()
                            ->enum(TransactionType::class)
                            ->default(TransactionType::EXPENSE)
                            ->hiddenOn(['edit'])
                            ->required(),

                        /** Categoria */
                        Select::make('transaction_category_id')
                            ->label('Categoria')
                            ->createOptionModalHeading('Criar categoria')
                            ->editOptionModalHeading('Editar categoria')
                            ->createOptionForm(self::categoryForm())
                            ->editOptionForm(self::categoryForm())
                            ->searchable()
                            ->preload()
                            ->relationship('category', 'name'),

                        /** Observações */
                        RichEditor::make('notes')
                            ->label('Observações')
                            ->extraInputAttributes(['style' => 'min-height: 10em'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function stepTwo(): Step
    {
        return Step::make('Valores')
            ->description(function (Get $get): ?string {
                if (CurrencyHelper::stringToFloat($get('total_amount')) <= 0) {
                    return null;
                }

                $value = Number::currency(CurrencyHelper::stringToFloat($get('total_amount')), 'BRL', 'pt_BR', 2);

                return match ($get->enum('payment_type', TransactionPaymentType::class)) {
                    TransactionPaymentType::SINGLE => "1x de $value",
                    TransactionPaymentType::INSTALLMENTS => "$value - Parcelado",
                    TransactionPaymentType::RECURRENT => $value.' - '.$get->enum('recurrency_type', TransactionRecurrencyType::class)?->getLabel() ?? 'N/A',

                };
            })
            ->afterValidation(self::updatePayments(...))
            ->schema([
                Section::make()
                    ->components([
                        /** Tipo de pagamento */
                        Radio::make('payment_type')
                            ->label('Tipo de pagamento')
                            ->options(TransactionPaymentType::class)
                            ->default(TransactionPaymentType::SINGLE)
                            ->hiddenOn(['edit'])
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('recurring_day', null);
                                $set('recurring_month', null);
                                $set('payments_count', null);
                            })
                            ->required(),

                        /** Valor */
                        CurrencyInput::make('total_amount')
                            ->label('Valor')
                            ->prefix('R$')
                            ->placeholder('Ex: 190,00')
                            ->rules(['required', 'numeric', 'min:0.01']),

                        /** Recorrência */
                        Select::make('recurrency_type')
                            ->label('Recorrência')
                            ->options(TransactionRecurrencyType::class)
                            ->default(TransactionRecurrencyType::MONTHLY)
                            ->native(false)
                            ->visible(fn (Get $get) => $get('payment_type') === TransactionPaymentType::RECURRENT)
                            ->live()
                            ->required(),

                        /** Data da transação */
                        DatePicker::make('transaction_date')
                            ->label('Data')
                            ->required()
                            ->default(now()),

                        Grid::make()
                            ->visible(fn (Get $get) => $get('payment_type') === TransactionPaymentType::RECURRENT)
                            ->schema([

                                /** Dia da recorrência  */
                                TextInput::make('recurring_day')
                                    ->label('Todo dia')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->placeholder('Dia do mês')
                                    ->columnSpan(fn (Get $get) => $get('recurrency_type') === TransactionRecurrencyType::MONTHLY || ! $get('recurrency_type') ? 2 : 1)
                                    ->required(),

                                /** Mês da recorrência  */
                                Select::make('recurring_month')
                                    ->label('Do mês')
                                    ->searchable()
                                    ->options(Month::class)
                                    ->required()
                                    ->visible(fn (Get $get) => $get('recurrency_type') === TransactionRecurrencyType::YEARLY),
                            ]),

                        /** Quantidade de parcelas */
                        TextInput::make('payments_count')
                            ->label('Quantidade de Parcelas')
                            ->step(1)
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->visible(fn (Get $get) => $get('payment_type') === TransactionPaymentType::INSTALLMENTS),
                    ]),
            ]);
    }

    public static function stepThree(): Step
    {
        return Step::make('Pagamentos')
            ->schema([
                Section::make()
                    ->components([

                        // Cobrado em
                        MorphToSelect::make('billable')
                            ->label('Cobrado em')
                            ->live()
                            ->native(false)
                            ->types(
                                array_map(fn ($type) => MorphToSelect\Type::make($type)
                                    ->label(match ($type) {
                                        CreditCard::class => 'Cartão de crédito',
                                        BankAccount::class => 'Conta Bancária (PIX / Transferência)'
                                    })
                                    ->titleAttribute('name')
                                    ->getOptionLabelFromRecordUsing(BillableHelper::getBillableOptionLabel(...)),
                                    [CreditCard::class, BankAccount::class])
                            )
                            ->modifyKeySelectUsing(fn (Select $select): Select => $select->searchable()->preload()->allowHtml())
                            ->afterStateUpdated(self::updatePayments(...)),

                        Grid::make()
                            ->components([
                                // Data da transação
                                TextEntry::make('transaction_date_info')
                                    ->label('Data da transação')
                                    ->badge()
                                    ->date('d/m/Y')
                                    ->state(fn (Get $get) => $get('transaction_date')),

                                /** Recurrency info */
                                TextEntry::make('recurrency')
                                    ->label('Vencimento')
                                    ->badge()
                                    ->disabled()
                                    ->hidden(fn (Get $get) => $get('payment_type') !== TransactionPaymentType::RECURRENT)
                                    ->state(function (Get $get) {

                                        if ($get('payment_type') !== TransactionPaymentType::RECURRENT) {
                                            return 'N/A';
                                        }

                                        $recurringDay = $get('recurring_day');
                                        $recurringMonth = $get('recurring_month');

                                        return match ($get('recurrency_type')) {
                                            TransactionRecurrencyType::MONTHLY => "Todo dia $recurringDay",
                                            TransactionRecurrencyType::YEARLY => "Todo dia $recurringDay do mês $recurringMonth",
                                            default => 'N/A'
                                        };

                                    }),
                            ]),

                        /** Itens/Installments */
                        Repeater::make('payments')
                            ->label(fn (Get $get) => str('Pagamento')->plural($get('payments_count')))
                            ->relationship()
                            ->table([
                                Repeater\TableColumn::make('#'),
                                Repeater\TableColumn::make('Valor'),
                                Repeater\TableColumn::make('Vencimento'),
                                Repeater\TableColumn::make('Pagamento'),
                                Repeater\TableColumn::make('Valor Pago'),
                                Repeater\TableColumn::make('Status'),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(self::mutatePaymentData(...))
                            ->mutateRelationshipDataBeforeSaveUsing(self::mutatePaymentData(...))
                            ->orderColumn('payment_number')
                            ->reorderable(false)
                            ->schema([

                                /** Número da parcela */
                                TextEntry::make('payment_number_info')
                                    ->label('Parcela')
                                    ->saved(false)
                                    ->state(fn (Get $get) => $get('payment_number'))
                                    ->disabled(),

                                /** Valor */
                                CurrencyInput::make('amount')
                                    ->label('Valor')
                                    ->prefix('R$')
                                    ->rules(['required', 'numeric', 'min:0.01']),

                                /** Data da cobrança */
                                DatePicker::make('billing_date')
                                    ->label('Vencimento')
                                    ->required(),

                                /** Data do pagamento */
                                DatePicker::make('payment_date')
                                    ->label('Pagamento')
                                    ->requiredIf('status', PaymentStatus::PAID),

                                /** Valor Pago */
                                CurrencyInput::make('paid_amount')
                                    ->label('Valor Pago')
                                    ->prefix('R$')
                                    ->requiredIf('status', PaymentStatus::PAID)
                                    ->rules(['numeric']),

                                /** Status */
                                Select::make('status')
                                    ->native(false)
                                    ->required()
                                    ->preload()
                                    ->live()
                                    ->selectablePlaceholder(false)
                                    ->afterStateUpdated(fn (Set $set) => $set('payment_date', null))
                                    ->options(PaymentStatus::class),
                            ])
                            ->visible(fn (Get $get) => filled($get('payments')))
                            ->addActionLabel('Adicionar parcela')
                            ->addable(fn (Get $get) => $get('payment_type') === TransactionPaymentType::INSTALLMENTS)
                            ->deletable(fn (Get $get) => $get('payment_type') !== TransactionPaymentType::SINGLE)
                            ->minItems(fn (Get $get) => $get('payments_count') ?: 1)
                            ->compact(),

                    ]),
            ]);
    }

    private static function updatePayments(Get $get, Set $set, ?Transaction $record): void
    {
        $needsToUpdate = ! $record
            || $get('payments_count') != $record->payments_count
            || CurrencyHelper::stringToFloat($get('total_amount')) !== (float) $record->total_amount;

        if (! $needsToUpdate) {
            return;
        }

        $transactionService = new TransactionsService;

        $payments = $transactionService->generatePayments(new TransactionDTO(
            name: $get('name'),
            transactionType: $get->enum('transaction_type', TransactionType::class),
            transactionCategory: TransactionCategory::find($get('transaction_category_id')),
            notes: $get('notes'),
            paymentType: $get->enum('payment_type', TransactionPaymentType::class),
            totalAmount: CurrencyHelper::stringToFloat($get('total_amount')),
            recurrencyType: $get->enum('recurrency_type', TransactionRecurrencyType::class),
            transactionDate: Carbon::parse($get('transaction_date')),
            recurringDay: $get('recurring_day'),
            recurringMonth: $get->enum('recurring_month', Month::class),
            paymentsCount: (int) $get('payments_count'),
            billableType: $get('billable_type'),
            billableId: $get('billable_id'),
        ));

        $set(
            'payments',
            $payments->map(fn (TransactionPayment $item) => $item
                ->fill([
                    'amount' => number_format($item->amount, 2, ',', '.'),
                    'paid_amount' => number_format($item->paid_amount, 2, ',', '.'),
                ]))
                ->toArray()
        );

    }

    private static function categoryForm(): array
    {
        return [
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->placeholder('Ex: Impostos'),
        ];
    }

    private static function mutatePaymentData(array $data, Get $get): array
    {
        return [
            ...$data,
            'billable_type' => $get('billable_type'),
            'billable_id' => $get('billable_id'),
        ];
    }
}
