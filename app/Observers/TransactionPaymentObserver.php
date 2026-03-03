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
