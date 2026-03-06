<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Filament\Resources\Payments\Widgets\AmountsByCategoryChart;
use App\Filament\Resources\Payments\Widgets\AmountsOverview;
use App\Filament\Resources\Payments\Widgets\ExpensesByBillableChart;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsUpDown;

    protected static ?string $navigationLabel = 'Fluxo de Caixa';

    protected static ?string $label = 'Pagamento';

    protected static ?string $recordTitleAttribute = 'payment_number';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'cash-flow';

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
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
            'index' => ListPayments::route('/'),
        ];
    }
}
