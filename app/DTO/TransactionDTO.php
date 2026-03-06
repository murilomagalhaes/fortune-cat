<?php

namespace App\DTO;

use App\Enums\Month;
use App\Enums\PaymentType;
use App\Enums\RecurrencyType;
use App\Enums\TransactionType;
use App\Models\TransactionCategory;
use Carbon\Carbon;

class TransactionDTO
{
    public function __construct(
        public string $name,
        public TransactionType $transactionType,
        public ?TransactionCategory $transactionCategory,
        public ?string $notes,
        public PaymentType $paymentType,
        public float $totalAmount,
        public ?RecurrencyType $recurrencyType,
        public Carbon $transactionDate,
        public ?int $recurringDay,
        public ?Month $recurringMonth,
        public ?int $paymentsCount, // Payments / Installments
        public ?string $billableType,
        public ?string $billableId,
    ) {
        if ($this->paymentType !== PaymentType::INSTALLMENTS || ! $this->paymentsCount) {
            $this->paymentsCount = 1;
        }

        if ($this->paymentType !== PaymentType::RECURRENT) {
            $this->recurringDay = null;
            $this->recurringMonth = null;
            $this->recurrencyType = null;
        }
    }
}
