<?php

namespace App\Filament\Resources\TransactionPayments;

use App\Filament\Resources\TransactionPayments\Pages\ListTransactionPayments;
use App\Filament\Resources\TransactionPayments\Tables\TransactionPaymentsTable;
use App\Filament\Resources\TransactionPayments\Widgets\AmountsByCategoryChart;
use App\Filament\Resources\TransactionPayments\Widgets\AmountsOverview;
use App\Filament\Resources\TransactionPayments\Widgets\ExpensesByBillableChart;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransactionPaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsUpDown;

    protected static ?string $navigationLabel = 'Fluxo de Caixa';

    protected static ?string $label = 'Pagamento';

    protected static ?string $recordTitleAttribute = 'payment_number';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return TransactionPaymentsTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['transaction.category']);
    }

    public static function getWidgets(): array
    {
        return [
            AmountsOverview::class,
            ExpensesByBillableChart::class,
            AmountsByCategoryChart::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactionPayments::route('/'),
        ];
    }
}
