<?php

namespace App\Services;

use App\DTO\TransactionDTO;
use App\Enums\TransactionPaymentType;
use App\Enums\TransactionRecurrencyType;
use App\Enums\PaymentStatus;
use App\Models\CreditCard;
use App\Models\TransactionPayment;
use Carbon\Carbon;

class TransactionsService
{
    public function generatePayments(TransactionDTO $transactionDTO): \Illuminate\Support\Collection
    {
        $billingDate = $transactionDTO->transactionDate->clone();
        $paymentsCount = $transactionDTO->paymentsCount;
        $amount = bcdiv($transactionDTO->totalAmount, $transactionDTO->paymentsCount, 2);

        if ($transactionDTO->paymentType === TransactionPaymentType::RECURRENT) {
            $billingDate->startOfMonth()->day($transactionDTO->recurringDay);
        } else if ($transactionDTO->billableType === CreditCard::class && $transactionDTO->billableId) {

            $creditCard = CreditCard::find($transactionDTO->billableId);

            $beforeBillingCycleEnd = $transactionDTO->transactionDate->day <= $creditCard->billing_cycle_end_date;

            $billingDate = $beforeBillingCycleEnd
                ? $transactionDTO->transactionDate->clone()->day($creditCard?->due_date)
                : $transactionDTO->transactionDate->clone()->addMonth()->day($creditCard->due_date);
        }

        $yearsSinceTransaction = ceil(Carbon::now()->diffInYears($transactionDTO->transactionDate, true));
        $monthsSinceTransaction = ceil(Carbon::now()->diffInMonths($transactionDTO->transactionDate, true));

        $paymentsCount = $transactionDTO->recurrencyType === TransactionRecurrencyType::MONTHLY ? $monthsSinceTransaction + 1 : $paymentsCount;
        $paymentsCount = $transactionDTO->recurrencyType === TransactionRecurrencyType::YEARLY ? $yearsSinceTransaction + 1 : $paymentsCount;

        $items = collect();

        foreach (range(1, $paymentsCount) as $installment) {

            $status = $billingDate->isPast() ? PaymentStatus::PAID : PaymentStatus::PENDING;

            $isPaid = $status === PaymentStatus::PAID;

            $items->push(new TransactionPayment([
                'amount' => $amount,
                'paid_amount' => $isPaid ? $amount : null,
                'billing_date' => $billingDate,
                'payment_date' => $isPaid ? $billingDate : null,
                'payment_number' => $installment,
                'billable_type' => $transactionDTO->billableType,
                'billable_id' => $transactionDTO->billableId,
                'status' => $status,
            ]));

            $billingDate = match ($transactionDTO->recurrencyType) {
                TransactionRecurrencyType::YEARLY => $billingDate->clone()->addYear(),
                default => $billingDate->clone()->addMonth(),
            };


        }

        return $items;
    }

}
