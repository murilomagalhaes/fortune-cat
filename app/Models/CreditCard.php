<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use App\Observers\BelongsToUserObserver;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BelongsToUserObserver::class])]
#[ScopedBy(UserScope::class)]
class CreditCard extends Model
{
    use BelongsToUser, HasFactory, HasUlids, SoftDeletes;

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
        return $this->morphMany(Payment::class, 'billable');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'billable');
    }

    public function availableLimit(): Attribute
    {
        return Attribute::make(get: function () {
            return (float) $this->total_limit - (float) $this->used_limit;
        });
    }

    public function title(): Attribute
    {
        return new Attribute(get: fn () => "[Cartão] {$this->name}");
    }
}
