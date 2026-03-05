<?php

namespace App\Filament\Resources\TransactionPayments\Widgets;

use App\Enums\TransactionType;
use App\Filament\Resources\TransactionPayments\Pages\ListTransactionPayments;
use App\Models\Payment;
use Carbon\Carbon;
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

        $selectedDate = Carbon::createFromDate($year ?? now()->year, $month ?? now()->month, 1);

        $chartRevenues = [];
        $chartExpenses = [];
        $chartBalances = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = $selectedDate->copy()->subMonths($i);
            $periodQuery = Payment::query()->filterBillingYearMonth($date->year, $date->month);

            $revenue = (clone $periodQuery)
                ->whereHas('transaction', fn ($q) => $q->where('transaction_type', TransactionType::REVENUE->value))
                ->sum('amount');

            $expense = (clone $periodQuery)
                ->whereHas('transaction', fn ($q) => $q->where('transaction_type', TransactionType::EXPENSE->value))
                ->sum('amount');

            $chartRevenues[] = $revenue;
            $chartExpenses[] = $expense;
            $chartBalances[] = $revenue - $expense;
        }

        $totalRevenue = last($chartRevenues);
        $totalExpense = last($chartExpenses);
        $balance = last($chartBalances);

        return [
            Stat::make('Receitas', 'R$ '.number_format($totalRevenue, 2, ',', '.'))
                ->description('Total de receitas')
                ->chart($chartRevenues)
                ->color('success'),
            Stat::make('Despesas', 'R$ '.number_format(abs($totalExpense), 2, ',', '.'))
                ->description('Total de despesas')
                ->chart($chartExpenses)
                ->color('danger'),
            Stat::make('Saldo', 'R$ '.number_format($balance, 2, ',', '.'))
                ->description('Saldo atual')
                ->chart($chartBalances)
                ->color($balance >= 0 ? 'success' : 'danger'),
        ];
    }
}
