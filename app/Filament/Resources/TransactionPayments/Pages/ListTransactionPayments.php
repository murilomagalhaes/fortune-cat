<?php

namespace App\Filament\Resources\TransactionPayments\Pages;

use App\Filament\Resources\TransactionPayments\TransactionPaymentResource;
use App\Filament\Resources\TransactionPayments\Widgets\AmountsByCategoryChart;
use App\Filament\Resources\TransactionPayments\Widgets\AmountsOverview;
use App\Filament\Resources\TransactionPayments\Widgets\ExpensesByBillableChart;
use App\Filament\Resources\Transactions\Schemas\TransactionForm;
use App\Models\Transaction;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ListTransactionPayments extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TransactionPaymentResource::class;

    protected static ?string $title = 'Fluxo de Caixa';

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->modal()
                ->modalWidth(Width::SixExtraLarge)
                ->steps(TransactionForm::steps())
                ->label('Nova transação')
                ->modalHeading('Criar transação')
                ->model(Transaction::class),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AmountsOverview::class
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ExpensesByBillableChart::class,
            AmountsByCategoryChart::class,
        ];
    }
}
