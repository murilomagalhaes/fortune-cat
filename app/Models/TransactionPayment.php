<?php

namespace App\Models;

use App\Enums\TransactionRecurrencyType;
use App\Enums\PaymentStatus;
use App\Observers\TransactionPaymentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy([TransactionPaymentObserver::class])]
class TransactionPayment extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionPaymentFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'billing_date' => 'date:Y-m-d',
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
        return $this->update(['status' => PaymentStatus::PENDING]);
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::PAID;
    }

    public function markAsPaid(?float $amount = null, ?string $billableType = null, mixed $billableId = null): bool
    {
        return $this->update([
            'amount' => $amount,
            'billable_type' => $billableType,
            'billable_id' => $billableId,
            'status' => PaymentStatus::PAID,
        ]);
    }

    public function scopeFilterBillingYearMonth(Builder $query, int $year, int $month): Builder
    {
        $padYear = str($year)->padLeft(4, '20');

        return $query
            ->leftJoinRelation('transaction')
            ->whereMonth('billing_date', $month)
            ->orWhere('recurring_month', $month)
            ->orWhere(
                fn(Builder $query) => $query
                    ->where('transactions.recurrency_type', TransactionRecurrencyType::MONTHLY)
                    ->where('transactions.transaction_date', '<=', now()->setYear($padYear->toInteger())->setMonth($month)->endOfMonth())
            )
            ->orWhere(
                column: fn(Builder $query) => $query
                    ->where('transactions.recurrency_type', TransactionRecurrencyType::YEARLY)
                    ->where('transactions.transaction_date', '<=', now()->setYear($padYear->toInteger())->setMonth($month)->endOfMonth())
                    ->whereYear('billing_date', $padYear->toInteger())
            );
    }
}
