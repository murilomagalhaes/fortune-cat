<?php

namespace App\Models;

use App\Enums\BankAccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    /** @use HasFactory<\Database\Factories\BankAccountFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'type' => BankAccountType::class,
            'balance' => 'float',
        ];
    }

    public function transactionItems(): MorphMany
    {
        return $this->morphMany(TransactionPayment::class, 'billable');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'billable');
    }


}
