<?php

namespace App\Filament\Resources\TransactionPayments\Widgets;

use App\Enums\TransactionType;
use App\Models\Payment;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class AmountsByCategoryChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected ?string $heading = 'Valores por Categoria';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    protected string $color = 'primary';

    protected function getData(): array
    {
        $year = data_get($this->tableFilters, 'billing_month_year.billing_year', now()->year);
        $month = data_get($this->tableFilters, 'billing_month_year.billing_month', now()->month);

        $payments = Payment::query()
            ->filterBillingYearMonth($year, $month)
            ->with(['transaction.category'])
            ->get();

        $grouped = $payments
            ->groupBy(fn (Payment $p) => $p->transaction->category?->name ?? 'Sem categoria')
            ->map(fn ($group) => round(
                $group->sum(fn (Payment $p) => $p->transaction->transaction_type === TransactionType::REVENUE ? $p->amount : -$p->amount
                ),
                2
            ))
            ->sortBy(fn ($v) => $v);

        $colors = $grouped->map(fn ($v) => $v >= 0 ? 'rgba(34, 197, 94)' : 'rgba(239, 68, 68)')->values()->toArray();
        $borders = $grouped->map(fn ($v) => $v >= 0 ? 'rgba(34, 197, 94)' : 'rgba(239, 68, 68)')->values()->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Valor',
                    'data' => $grouped->values()->toArray(),
                    'backgroundColor' => $colors,
                    'borderColor' => $borders,
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
                        callback: (value) => 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (context) => 'R$ ' + context.raw.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                    }
                }
            }
        }
        JS);
    }
}
