<?php

namespace App\Models;

use App\Enums\RecurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Observers\TransactionPaymentObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

#[ObservedBy([TransactionPaymentObserver::class])]
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionPaymentFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'billing_date' => 'date:Y-m-d',
            'payment_date' => 'date:Y-m-d',
        ];
    }

    public function billable(): MorphTo
    {
        return $this->morphTo();
    }


    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function markAsPending(): bool
    {
        if ($this->isPending()) {
            return true;
        }

        return $this->update([
            'status' => PaymentStatus::PENDING,
            'payment_date' => null,
            'paid_amount' => null,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::PAID;
    }

    public function isRecurring(): bool
    {
        return $this->monthlyRecurrency() || $this->yearlyRecurrency();
    }

    public function monthlyRecurrency(): bool
    {
        $this->loadMissing('transaction');

        return $this->transaction->recurrency_type === RecurrencyType::MONTHLY;
    }

    public function yearlyRecurrency(): bool
    {
        $this->loadMissing('transaction');

        return $this->transaction->recurrency_type === RecurrencyType::YEARLY;
    }

    public function isRevenue(): bool
    {
        $this->loadMissing('transaction');

        return $this->transaction->transaction_type === TransactionType::REVENUE;
    }

    public function isExpense(): bool
    {
        $this->loadMissing('transaction');

        return $this->transaction->transaction_type === TransactionType::EXPENSE;

    }

    public function markAsPaid(
        ?float             $paidAmount = null,
        null|string|Carbon $paymentDate = null,
        ?string            $billableType = null,
        mixed              $billableId = null
    ): bool
    {
        if ($this->isPaid()) {
            return true;
        }

        return $this->update([
            'paid_amount' => $paidAmount ?: $this->amount,
            'billable_type' => $billableType,
            'billable_id' => $billableId,
            'status' => PaymentStatus::PAID,
            'payment_date' => $paymentDate ?: now()->format('Y-m-d'),
        ]);
    }

    public function scopeFilterBillingYearMonth(Builder $query, int $year, int $month): Builder
    {
        $padYear = str($year)->padLeft(4, '20')->toInteger();
        $lastDayOfMonth = Carbon::createFromDate($padYear, $month)->endOfMonth()->toDateString();

        return $query
            ->leftJoinRelation('transaction')
            ->where(function (Builder $query) use ($month, $padYear, $lastDayOfMonth) {
                $query
                    ->where(function (Builder $query) use ($month, $padYear) {
                        $query->whereMonth('payments.billing_date', '=', $month)
                            ->whereYear('payments.billing_date', '=', $padYear);
                    })
                    // Shows the last pending payment for the recurring months
                    ->orWhere(function (Builder $query) use ($lastDayOfMonth) {
                        $query->where('transactions.recurrency_type', '=', RecurrencyType::MONTHLY)
                            ->where('payments.status', '=', PaymentStatus::PENDING)
                            ->where('payments.billing_date', '<=', $lastDayOfMonth)
                            ->where('payments.billing_date', '=', function (QueryBuilder $subQuery) use ($lastDayOfMonth) {
                                $subQuery->selectRaw('MAX(tp2.billing_date)')
                                    ->from('payments as tp2')
                                    ->whereColumn('tp2.transaction_id', 'payments.transaction_id')
                                    ->where('tp2.status', '=', PaymentStatus::PENDING)
                                    ->where('tp2.billing_date', '<=', $lastDayOfMonth);
                            });
                    })
                    // Shows the last pending payment for the recurring month/year
                    ->orWhere(function (Builder $query) use ($month, $padYear) {
                        $query->where('transactions.recurrency_type', '=', RecurrencyType::YEARLY)
                            ->where('payments.status', '=', PaymentStatus::PENDING)
                            ->whereMonth('payments.billing_date', '=', $month)
                            ->whereYear('payments.billing_date', '<=', $padYear)
                            ->where('payments.billing_date', '=', function (QueryBuilder $subQuery) use ($month, $padYear) {
                                $subQuery->selectRaw('MAX(tp2.billing_date)')
                                    ->from('payments as tp2')
                                    ->whereColumn('tp2.transaction_id', 'payments.transaction_id')
                                    ->where('tp2.status', '=', PaymentStatus::PENDING)
                                    ->whereMonth('tp2.billing_date', '=', $month)
                                    ->whereYear('tp2.billing_date', '<=', $padYear);
                            });
                    });
            });
    }
}
