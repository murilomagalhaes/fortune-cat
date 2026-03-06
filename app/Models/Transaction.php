<?php

namespace App\Models;

use App\Enums\PaymentType;
use App\Enums\RecurrencyType;
use App\Enums\TransactionType;
use App\Models\Scopes\UserScope;
use App\Observers\BelongsToUserObserver;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy([BelongsToUserObserver::class])]
#[ScopedBy(UserScope::class)]
class Transaction extends Model
{
    use BelongsToUser, HasFactory, HasUlids;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $withCount = ['payments'];

    protected function casts(): array
    {
        return [
            'transaction_type' => TransactionType::class,
            'payment_type' => PaymentType::class,
            'recurrency_type' => RecurrencyType::class,
            'transaction_date' => 'date:Y-m-d',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function billable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function isRecurring(): bool
    {
        return $this->payment_type === PaymentType::RECURRENT;
    }
}
