<?php

use App\Enums\PaymentStatus;
use App\Models\BankAccount;
use App\Models\CreditCard;
use App\Models\TransactionPayment;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('TransactionPaymentObserver::updated', function () {
    describe('billable is a BankAccount', function () {
        it('decrements the bank account balance when status changes to paid', function () {
            $bankAccount = BankAccount::factory()->create(['balance' => 1000.00]);

            $payment = TransactionPayment::factory()->pending()->create([
                'amount' => 200.00,
                'billable_type' => BankAccount::class,
                'billable_id' => $bankAccount->id,
            ]);

            $payment->update(['status' => PaymentStatus::PAID]);

            expect($bankAccount->fresh()->balance)->toBe(800.00);
        });

        it('increments the bank account balance when status changes to pending', function () {
            $bankAccount = BankAccount::factory()->create(['balance' => 800.00]);

            $payment = TransactionPayment::factory()->paid()->create([
                'amount' => 200.00,
                'billable_type' => BankAccount::class,
                'billable_id' => $bankAccount->id,
            ]);

            $payment->update(['status' => PaymentStatus::PENDING]);

            expect($bankAccount->fresh()->balance)->toBe(1000.00);
        });

        it('does not change balance when status is not changed', function () {
            $bankAccount = BankAccount::factory()->create(['balance' => 1000.00]);

            $payment = TransactionPayment::factory()->pending()->create([
                'amount' => 200.00,
                'billable_type' => BankAccount::class,
                'billable_id' => $bankAccount->id,
            ]);

            $payment->update(['amount' => 300.00]);

            expect($bankAccount->fresh()->balance)->toBe(1000.00);
        });
    });

    describe('billable is a CreditCard without a linked bank account', function () {
        it('decrements the credit card used_limit when status changes to paid', function () {
            $creditCard = CreditCard::factory()->create([
                'total_limit' => 5000.00,
                'used_limit' => 300.00,
                'bank_account_id' => null,
            ]);

            $payment = TransactionPayment::factory()->pending()->create([
                'amount' => 300.00,
                'billable_type' => CreditCard::class,
                'billable_id' => $creditCard->id,
            ]);

            $payment->update(['status' => PaymentStatus::PAID]);

            expect($creditCard->fresh()->used_limit)->toBe(0.00);
        });

        it('increments the credit card used_limit when status changes to pending', function () {
            $creditCard = CreditCard::factory()->create([
                'total_limit' => 5000.00,
                'used_limit' => 0.00,
                'bank_account_id' => null,
            ]);

            $payment = TransactionPayment::factory()->paid()->create([
                'amount' => 300.00,
                'billable_type' => CreditCard::class,
                'billable_id' => $creditCard->id,
            ]);

            $payment->update(['status' => PaymentStatus::PENDING]);

            expect($creditCard->fresh()->used_limit)->toBe(300.00);
        });
    });

    describe('billable is a CreditCard with a linked bank account', function () {
        it('decrements used_limit and bank account balance when status changes to paid', function () {
            $bankAccount = BankAccount::factory()->create(['balance' => 2000.00]);

            $creditCard = CreditCard::factory()->create([
                'total_limit' => 5000.00,
                'used_limit' => 400.00,
                'bank_account_id' => $bankAccount->id,
            ]);

            $payment = TransactionPayment::factory()->pending()->create([
                'amount' => 400.00,
                'billable_type' => CreditCard::class,
                'billable_id' => $creditCard->id,
            ]);

            $payment->update(['status' => PaymentStatus::PAID]);

            expect($creditCard->fresh()->used_limit)->toBe(0.00);
            expect($bankAccount->fresh()->balance)->toBe(1600.00);
        });

        it('increments used_limit and bank account balance when status changes to pending', function () {
            $bankAccount = BankAccount::factory()->create(['balance' => 1600.00]);

            $creditCard = CreditCard::factory()->create([
                'total_limit' => 5000.00,
                'used_limit' => 0.00,
                'bank_account_id' => $bankAccount->id,
            ]);

            $payment = TransactionPayment::factory()->paid()->create([
                'amount' => 400.00,
                'billable_type' => CreditCard::class,
                'billable_id' => $creditCard->id,
            ]);

            $payment->update(['status' => PaymentStatus::PENDING]);

            expect($creditCard->fresh()->used_limit)->toBe(400.00);
            expect($bankAccount->fresh()->balance)->toBe(2000.00);
        });
    });
});