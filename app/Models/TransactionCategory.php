<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use App\Observers\BelongsToUserObserver;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([BelongsToUserObserver::class])]
#[ScopedBy(UserScope::class)]
class TransactionCategory extends Model
{
    use BelongsToUser, HasFactory, HasUlids;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}
