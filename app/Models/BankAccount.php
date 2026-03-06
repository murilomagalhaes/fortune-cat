<?php

namespace App\Models;

use App\Enums\BankAccountType;
use App\Models\Scopes\UserScope;
use App\Observers\BelongsToUserObserver;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([BelongsToUserObserver::class])]
#[ScopedBy(UserScope::class)]
class BankAccount extends Model
{
    use BelongsToUser, HasFactory, HasUlids, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at', 'user_id'];

    protected $appends = ['title'];

    protected function casts(): array
    {
        return [
            'type' => BankAccountType::class,
            'balance' => 'float',
        ];
    }

    public function transactionItems(): MorphMany
    {
        return $this->morphMany(Payment::class, 'billable');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'billable');
    }

    public function title(): Attribute
    {
        return new Attribute(get: fn () => "[Conta] {$this->name}");
    }
}
