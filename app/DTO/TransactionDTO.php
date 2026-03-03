<?php

namespace App\DTO;

use App\Enums\Month;
use App\Enums\TransactionPaymentType;
use App\Enums\TransactionRecurrencyType;
use App\Enums\TransactionType;
use App\Models\TransactionCategory;
use Carbon\Carbon;

class TransactionDTO
{
    public function __construct(
        public string                     $name,
        public TransactionType            $transactionType,
        public ?TransactionCategory       $transactionCategory,
        public ?string                    $notes,
        public TransactionPaymentType     $paymentType,
        public float                      $totalAmount,
        public ?TransactionRecurrencyType $recurrencyType,
        public Carbon                     $transactionDate,
        public ?int                       $recurringDay,
        public ?Month                     $recurringMonth,
        public ?int                       $paymentsCount, // Payments / Installments
        public ?string                    $billableType,
        public ?int                       $billableId,
    )
    {
        if ($this->paymentType !== TransactionPaymentType::INSTALLMENTS || !$this->paymentsCount) {
            $this->paymentsCount = 1;
        }

        if ($this->paymentType !== TransactionPaymentType::RECURRENT) {
            $this->recurringDay = null;
            $this->recurringMonth = null;
            $this->recurrencyType = null;
        }
    }

}
