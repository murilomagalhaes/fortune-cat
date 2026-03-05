<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditCard extends Model
{
    /** @use HasFactory<\Database\Factories\CreditCardFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected $appends = ['available_limit', 'title'];

    protected function casts(): array
    {
        return [
            'total_limit' => 'float',
            'used_limit' => 'float',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transactionItems(): MorphMany
    {
        return $this->morphMany(TransactionPayment::class, 'billable');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'billable');
    }

    public function availableLimit(): Attribute
    {
        return Attribute::make(get: function () {
            return (float)$this->total_limit - (float)$this->used_limit;
        });
    }

    public function title(): Attribute
    {
        return new Attribute(get: fn() => "[Cartão] {$this->name}");
    }
}
