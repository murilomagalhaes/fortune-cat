<?php

namespace App\Filament\Resources\TransactionPayments\Widgets;

use App\Enums\TransactionType;
use App\Filament\Resources\TransactionPayments\Pages\ListTransactionPayments;
use App\Models\TransactionPayment;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AmountsOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListTransactionPayments::class;
    }

    protected function getStats(): array
    {
        $year = data_get($this->tableFilters, 'billing_month_year.billing_year');
        $month = data_get($this->tableFilters, 'billing_month_year.billing_month');

        $query =  TransactionPayment::query()
            ->filterBillingYearMonth($year, $month);

        $totalRevenue = (clone $query)
            ->whereHas('transaction', fn ($q) => $q->where('transaction_type', TransactionType::REVENUE->value))
            ->sum('amount');

        $totalExpense = (clone $query)
            ->whereHas('transaction', fn ($q) => $q->where('transaction_type', TransactionType::EXPENSE->value))
            ->sum('amount');

        $balance = $totalRevenue - $totalExpense;

        return [
            Stat::make('Receitas', 'R$ '.number_format($totalRevenue, 2, ',', '.'))
                ->description('Total de receitas')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),
            Stat::make('Despesas', 'R$ '.number_format(abs($totalExpense), 2, ',', '.'))
                ->description('Total de despesas')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('danger'),
            Stat::make('Saldo', 'R$ '.number_format($balance, 2, ',', '.'))
                ->description('Saldo atual')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color($balance >= 0 ? 'success' : 'danger'),
        ];
    }
}
