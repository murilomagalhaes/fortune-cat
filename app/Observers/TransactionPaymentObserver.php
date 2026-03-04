<?php

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Enums\TransactionRecurrencyType;
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
            $this->handleRecurrency($transactionPayment);
            $this->handleBalanceUpdate($transactionPayment);
        }
    }

    private function handleBalanceUpdate(TransactionPayment $transactionPayment): void
    {
        $transactionPayment->loadMissing('billable');

        $getBankAccountUpdatedBalance = fn(?float $currentBalance) => match (true) {
            $transactionPayment->isPaid() && $transactionPayment->isRevenue() => $currentBalance + $transactionPayment->paid_amount,
            $transactionPayment->isPaid() && $transactionPayment->isExpense() => $currentBalance - $transactionPayment->paid_amount,
            $transactionPayment->isPending() && $transactionPayment->isRevenue() => $currentBalance - $transactionPayment->paid_amount,
            $transactionPayment->isPending() && $transactionPayment->isExpense() => $currentBalance + $transactionPayment->paid_amount,
            default => $currentBalance,
        };

        if ($transactionPayment->billable instanceof BankAccount) {
            $transactionPayment->billable->update(['balance' => $getBankAccountUpdatedBalance($transactionPayment->billable->balance)]);
        }

        if ($transactionPayment->billable instanceof CreditCard) {

            $usedLimit = match (true) {
                $transactionPayment->isPaid() => $transactionPayment->billable->used_limit - $transactionPayment->paid_amount,
                $transactionPayment->isPending() => $transactionPayment->billable->used_limit + $transactionPayment->paid_amount,
                default => $transactionPayment->billable->used_limit,
            };

            $transactionPayment->billable->update(['used_limit' => $usedLimit]);

            if ($transactionPayment->billable->bank_account_id) {
                $bankAccount = BankAccount::find($transactionPayment->billable->bank_account_id);
                $bankAccount->update(['balance' => $getBankAccountUpdatedBalance($bankAccount->balance)]);
            }

        }
    }

    private function handleRecurrency(TransactionPayment $transactionPayment): void
    {
        if (!$transactionPayment->transaction->isRecurring()) {
            return;
        }

        if ($transactionPayment->isPaid()) {
            $billingDate = match ($transactionPayment->transaction->recurrency_type) {
                TransactionRecurrencyType::YEARLY => $transactionPayment->billing_date
                    ->startOfYear()
                    ->addYear()
                    ->month($transactionPayment->transaction->recurring_month)
                    ->day($transactionPayment->transaction->recurring_day),
                default => $transactionPayment->billing_date
                    ->startOfMonth()
                    ->addMonth()
                    ->day($transactionPayment->transaction->recurring_day),
            };

            TransactionPayment::query()
                ->firstOrCreate(
                    [
                        'transaction_id' => $transactionPayment->transaction_id,
                        'payment_number' => $transactionPayment->payment_number + 1,
                    ],
                    [
                        'amount' => $transactionPayment->amount,
                        'billing_date' => $billingDate,
                        'billable_type' => $transactionPayment->billable_type,
                        'billable_id' => $transactionPayment->billable_id,
                        'status' => PaymentStatus::PENDING,
                    ]
                );


        }


    }
}
