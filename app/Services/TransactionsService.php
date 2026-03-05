<?php

namespace App\Services;

use App\DTO\TransactionDTO;
use App\Enums\PaymentType;
use App\Enums\RecurrencyType;
use App\Enums\PaymentStatus;
use App\Models\CreditCard;
use App\Models\Payment;
use Carbon\Carbon;

class TransactionsService
{
    public function generatePayments(TransactionDTO $dto): \Illuminate\Support\Collection
    {
        $billingDate = $dto->transactionDate->clone();
        $paymentsCount = $dto->paymentsCount;
        $amount = bcdiv($dto->totalAmount, $dto->paymentsCount, 2);

        if ($dto->paymentType === PaymentType::RECURRENT) {
            $billingDate->startOfMonth()->day($dto->recurringDay);
        } else if ($dto->billableType === CreditCard::class && $dto->billableId) {

            $creditCard = CreditCard::find($dto->billableId);

            $beforeBillingCycleEnd = $dto->transactionDate->day <= $creditCard->billing_cycle_end_date;

            $billingDate = $beforeBillingCycleEnd
                ? $dto->transactionDate->clone()->day($creditCard?->due_date)
                : $dto->transactionDate->clone()->addMonth()->day($creditCard->due_date);
        }

        $yearsSinceTransaction = ceil(Carbon::now()->diffInYears($dto->transactionDate, true));
        $monthsSinceTransaction = ceil(Carbon::now()->diffInMonths($dto->transactionDate, true));

        $paymentsCount = $dto->recurrencyType === RecurrencyType::MONTHLY ? $monthsSinceTransaction + 1 : $paymentsCount;
        $paymentsCount = $dto->recurrencyType === RecurrencyType::YEARLY ? $yearsSinceTransaction + 1 : $paymentsCount;

        $items = collect();

        foreach (range(1, $paymentsCount) as $paymentNumber) {

            $status = $billingDate->isPast() ? PaymentStatus::PAID : PaymentStatus::PENDING;

            $isPaid = $status === PaymentStatus::PAID;

            $items->push(new Payment([
                'amount' => $amount,
                'paid_amount' => $isPaid ? $amount : null,
                'billing_date' => $billingDate,
                'payment_date' => $isPaid ? $billingDate : null,
                'payment_number' => $paymentNumber,
                'billable_type' => $dto->billableType,
                'billable_id' => $dto->billableId,
                'status' => $status,
            ]));

            $billingDate = match ($dto->recurrencyType) {
                RecurrencyType::YEARLY => $billingDate->clone()->addYear(),
                default => $billingDate->clone()->addMonth(),
            };


        }

        return $items;
    }

}
