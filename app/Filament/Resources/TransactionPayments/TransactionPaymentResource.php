<?php

namespace App\Filament\Resources\TransactionPayments;

use App\Enums\NavigationGroup;
use App\Filament\Resources\TransactionPayments\Pages\ListTransactionPayments;
use App\Filament\Resources\TransactionPayments\Tables\TransactionPaymentsTable;
use App\Models\TransactionPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransactionPaymentResource extends Resource
{
    protected static ?string $model = TransactionPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $label = 'Pagamento';

    protected static ?string $recordTitleAttribute = 'payment_number';

    protected static ?int $navigationSort = 1;

    protected static null|string|\UnitEnum $navigationGroup = NavigationGroup::TRANSACTIONS;

    public static function table(Table $table): Table
    {
        return TransactionPaymentsTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['transaction.category']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactionPayments::route('/'),
        ];
    }
}
