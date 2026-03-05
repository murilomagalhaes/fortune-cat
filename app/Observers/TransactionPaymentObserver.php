<?php

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Enums\RecurrencyType;
use App\Models\BankAccount;
use App\Models\CreditCard;
use App\Models\Payment;

class TransactionPaymentObserver
{

    public function updated(Payment $payment): void
    {
        $payment->load('transaction');

        if ($payment->wasChanged(['billable_type', 'billable_id'])) {
            $payment->transaction->updateQuietly([
                'billable_type' => $payment->billable_type,
                'billable_id' => $payment->billable_id,
            ]);
        }

        if ($payment->wasChanged('status')) {
            $this->handleRecurrency($payment);
            $this->handleBalanceUpdate($payment);
        }
    }

    private function handleBalanceUpdate(Payment $payment): void
    {
        $payment->loadMissing('billable');

        $getBankAccountUpdatedBalance = fn(?float $currentBalance) => match (true) {
            $payment->isPaid() && $payment->isRevenue() => $currentBalance + $payment->paid_amount,
            $payment->isPaid() && $payment->isExpense() => $currentBalance - $payment->paid_amount,
            $payment->isPending() && $payment->isRevenue() => $currentBalance - $payment->paid_amount,
            $payment->isPending() && $payment->isExpense() => $currentBalance + $payment->paid_amount,
            default => $currentBalance,
        };

        if ($payment->billable instanceof BankAccount) {
            $payment->billable->update(['balance' => $getBankAccountUpdatedBalance($payment->billable->balance)]);
        }

        if ($payment->billable instanceof CreditCard) {

            $usedLimit = match (true) {
                $payment->isPaid() => $payment->billable->used_limit - $payment->paid_amount,
                $payment->isPending() => $payment->billable->used_limit + $payment->paid_amount,
                default => $payment->billable->used_limit,
            };

            $payment->billable->update(['used_limit' => $usedLimit]);

            if ($payment->billable->bank_account_id) {
                $bankAccount = BankAccount::find($payment->billable->bank_account_id);
                $bankAccount->update(['balance' => $getBankAccountUpdatedBalance($bankAccount->balance)]);
            }

        }
    }

    private function handleRecurrency(Payment $payment): void
    {
        if (!$payment->transaction->isRecurring()) {
            return;
        }

        if ($payment->isPaid()) {

            $billingDate = match ($payment->transaction->recurrency_type) {
                RecurrencyType::YEARLY => $payment->billing_date
                    ->startOfYear()
                    ->addYear()
                    ->month($payment->transaction->recurring_month)
                    ->day($payment->transaction->recurring_day),
                default => $payment->billing_date
                    ->startOfMonth()
                    ->addMonth()
                    ->day($payment->transaction->recurring_day),
            };

            Payment::query()
                ->firstOrCreate(
                    [
                        'transaction_id' => $payment->transaction_id,
                        'payment_number' => $payment->payment_number + 1,
                    ],
                    [
                        'amount' => $payment->amount,
                        'billing_date' => $billingDate,
                        'billable_type' => $payment->billable_type,
                        'billable_id' => $payment->billable_id,
                        'status' => PaymentStatus::PENDING,
                    ]
                );


        }


    }
}
