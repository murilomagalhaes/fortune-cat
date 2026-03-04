<?php

namespace App\Filament\Resources\TransactionPayments\Widgets;

use App\Enums\TransactionType;
use App\Filament\Resources\TransactionPayments\Pages\ListTransactionPayments;
use App\Models\TransactionPayment;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class ExpensesByBillableChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListTransactionPayments::class;
    }

    protected ?string $heading = 'Despesas por Carteira';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $year = data_get($this->tableFilters, 'billing_month_year.billing_year', now()->year);
        $month = data_get($this->tableFilters, 'billing_month_year.billing_month', now()->month);

        $payments = TransactionPayment::query()
            ->filterBillingYearMonth($year, $month)
            ->where('transactions.transaction_type', TransactionType::EXPENSE->value)
            ->with(['billable'])
            ->get();

        $grouped = $payments
            ->groupBy(fn (TransactionPayment $p) => $p->billable?->name ?? 'N/A')
            ->map(fn ($group) => round(-$group->sum('amount'), 2))
            ->sortBy(fn ($v) => $v);

        return [
            'datasets' => [
                [
                    'label' => 'Despesas',
                    'data' => $grouped->values()->toArray(),
                    'backgroundColor' => array_fill(0, $grouped->count(), 'rgba(239, 68, 68, 0.8)'),
                    'borderColor' => array_fill(0, $grouped->count(), 'rgba(239, 68, 68, 1)'),
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $grouped->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            scales: {
                y: {
                    ticks: {
                        callback: (value) => 'R$ ' + Math.abs(value).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => 'R$ ' + Math.abs(context.raw).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                    }
                }
            }
        }
        JS);
    }
}
