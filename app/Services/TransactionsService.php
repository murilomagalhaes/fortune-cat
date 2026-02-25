<?php

namespace App\Services;

use App\DTO\TransactionDTO;
use App\Enums\TransactionPaymentType;
use App\Enums\TransactionRecurrencyType;
use App\Enums\PaymentStatus;
use App\Models\CreditCard;
use App\Models\TransactionPayment;

class TransactionsService
{
    public function generatePayments(TransactionDTO $transactionDTO): \Illuminate\Support\Collection
    {
        $billingDate = $transactionDTO->transactionDate->clone();

        $amount = bcdiv($transactionDTO->totalAmount, $transactionDTO->itemsCount, 2);

        if ($transactionDTO->billableType === CreditCard::class && $transactionDTO->billableId) {

            $creditCard = CreditCard::find($transactionDTO->billableId);

            $beforeBillingCycleEnd = $transactionDTO->transactionDate->day <= $creditCard->billing_cycle_end_date;

            $billingDate = $beforeBillingCycleEnd
                ? $transactionDTO->transactionDate->clone()->day($creditCard?->due_date)
                : $transactionDTO->transactionDate->clone()->addMonth()->day($creditCard->due_date);
        }

        $items = collect();

        foreach (range(1, $transactionDTO->itemsCount) as $installment) {

            $status = $billingDate->isPast() ? PaymentStatus::PAID : PaymentStatus::PENDING;

            if ($transactionDTO->recurrencyType === TransactionRecurrencyType::MONTHLY) {

                $status = now()->day >= $transactionDTO->recurringDay
                    ? PaymentStatus::PAID
                    : PaymentStatus::PENDING;

            } else if ($transactionDTO->recurrencyType === TransactionRecurrencyType::YEARLY) {

                $status = now()->month == $transactionDTO->recurringMonth && now()->day >= $transactionDTO->recurringDay
                    ? PaymentStatus::PAID
                    : PaymentStatus::PENDING;

            }

            $items->push(new TransactionPayment([
                'amount' => $amount,
                'billing_date' => match (true) {
                    $transactionDTO->paymentType !== TransactionPaymentType::RECURRENT => $billingDate->clone(),
                    $transactionDTO->paymentType === TransactionPaymentType::RECURRENT && $status === PaymentStatus::PAID => $billingDate->clone()
                        ->startOfMonth()
                        ->addDays($transactionDTO->recurringDay),
                    default => null
                },
                'payment_number' => $installment,
                'billable_type' => $transactionDTO->billableType,
                'billable_id' => $transactionDTO->billableId,
                'status' => $status,
            ]));

            $billingDate->addMonth();

        }

        return $items;
    }

}
