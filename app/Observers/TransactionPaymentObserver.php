<?php

namespace App\Observers;

use App\Models\BankAccount;
use App\Models\CreditCard;
use App\Models\TransactionPayment;

class TransactionPaymentObserver
{
    public function created(TransactionPayment $transactionPayment): void
    {

    }

    public function updated(TransactionPayment $transactionPayment): void
    {
        $transactionPayment->load('transaction');

        if ($transactionPayment->wasChanged(['billable_type', 'billable_id'])) {
            $transactionPayment->transaction->updateQuietly([
                'billable_type' => $transactionPayment->billable_type,
                'billable_id' => $transactionPayment->billable_id,
            ]);
        }

        if ($transactionPayment->wasChanged('status')) {
            $this->syncBillableBalance($transactionPayment);
        }
    }

    public function deleted(TransactionPayment $transactionPayment): void
    {
    }

    private function syncBillableBalance(TransactionPayment $transactionPayment): void
    {
        $billable = $transactionPayment->billable;
        $amount = (float) $transactionPayment->amount;
        $changedToPaid = $transactionPayment->isPaid();

        if ($billable instanceof BankAccount) {
            $changedToPaid
                ? $billable->decrement('balance', $amount)
                : $billable->increment('balance', $amount);
        }

        if ($billable instanceof CreditCard) {
            $changedToPaid
                ? $billable->decrement('used_limit', $amount)
                : $billable->increment('used_limit', $amount);

            $billable->load('bankAccount');

            if ($billable->bankAccount) {
                $changedToPaid
                    ? $billable->bankAccount->decrement('balance', $amount)
                    : $billable->bankAccount->increment('balance', $amount);
            }
        }
    }
}
