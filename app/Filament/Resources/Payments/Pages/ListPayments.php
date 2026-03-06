<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\Month;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Widgets\AmountsByCategoryChart;
use App\Filament\Resources\Payments\Widgets\AmountsOverview;
use App\Filament\Resources\Payments\Widgets\ExpensesByBillableChart;
use App\Filament\Resources\Transactions\Schemas\TransactionForm;
use App\Models\Transaction;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ListPayments extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = PaymentResource::class;


    public function getTitle(): string|Htmlable
    {
        $year = data_get($this->tableFilters, 'billing_month_year.billing_year');
        $month = data_get($this->tableFilters, 'billing_month_year.billing_month');

        if ($year && $month) {
            $monthEnum = Month::tryFrom($month);
            $padYear = str($year)->padLeft(4, '20');
            return "Fluxo de Caixa ({$monthEnum->getLabel()} {$padYear})";
        }

        return "Fluxo de Caixa";
    }

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
