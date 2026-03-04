<?php

namespace App\Filament\Resources\TransactionPayments\Widgets;

use App\Enums\TransactionType;
use App\Filament\Resources\TransactionPayments\Pages\ListTransactionPayments;
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
        $query = $this->getPageTableQuery();

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
                ->color('success'),
            Stat::make('Despesas', 'R$ '.number_format(abs($totalExpense), 2, ',', '.'))
                ->description('Total de despesas')
                ->color('danger'),
            Stat::make('Saldo', 'R$ '.number_format($balance, 2, ',', '.'))
                ->description('Saldo atual')
                ->color($balance >= 0 ? 'success' : 'danger'),
        ];
    }
}
