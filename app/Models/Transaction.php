<?php

namespace App\Models;

use App\Enums\TransactionPaymentType;
use App\Enums\TransactionRecurrencyType;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $withCount = ['payments'];

    protected function casts(): array
    {
        return [
            'transaction_type' => TransactionType::class,
            'payment_type' => TransactionPaymentType::class,
            'recurrency_type' => TransactionRecurrencyType::class,
            'transaction_date' => 'date:Y-m-d',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransactionPayment::class);
    }

    public function billable(): MorphTo
    {
        return $this->morphTo();
    }


}
