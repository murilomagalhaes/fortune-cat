<?php

namespace App\Filament\Resources\TransactionPayments\Widgets;

use App\Enums\TransactionType;
use App\Filament\Resources\TransactionPayments\Pages\ListTransactionPayments;
use App\Models\Payment;
use Filament\Support\Colors\Color;
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

        $payments = Payment::query()
            ->filterBillingYearMonth($year, $month)
            ->where('transactions.transaction_type', TransactionType::EXPENSE->value)
            ->with(['billable'])
            ->get();

        $grouped = $payments
            ->groupBy(fn (Payment $p) => $p->billable?->title ?? 'N/A')
            ->map(fn ($group) => round($group->sum('amount'), 2))
            ->sortByDesc(fn ($v) => $v);


        $billableColors = $payments
            ->sortByDesc(fn ($p) => $p->amount)
            ->groupBy(fn (Payment $p) => $p->billable)
            ->map(fn ($group) => $group->first()->billable?->color ?? '#CCCCCC')
            ->values()
            ->toArray();


        return [
            'datasets' => [
                [
                    'label' => 'Despesas',
                    'data' => $grouped->values()->toArray(),
                    'backgroundColor' => $billableColors,
                    'borderColor' => $billableColors,
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
