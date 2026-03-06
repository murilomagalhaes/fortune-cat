<?php

namespace Database\Seeders;

use App\DTO\TransactionDTO;
use App\Enums\BankAccountType;
use App\Enums\Month;
use App\Enums\PaymentType;
use App\Enums\RecurrencyType;
use App\Enums\TransactionType;
use App\Models\BankAccount;
use App\Models\CreditCard;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\TransactionsService;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class FakeDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(private readonly TransactionsService $transactionsService)
    {
    }

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $categories = $this->createCategories($user->id);
        [$checking, $savings] = $this->createBankAccounts($user->id);
        $creditCard = $this->createCreditCard($user->id, $checking->id);

        $this->seedSingleTransactions($user->id, $categories, $checking, $savings);
        $this->seedInstallmentTransactions($user->id, $categories, $creditCard);
        $this->seedRecurringTransactions($user->id, $categories, $checking, $savings);
    }

    private function createCategories(string $userId): Collection
    {
        $names = ['Alimentação', 'Transporte', 'Saúde', 'Lazer', 'Moradia', 'Salário', 'Freelance'];

        return collect($names)->map(fn(string $name) => TransactionCategory::factory()->create([
            'user_id' => $userId,
            'name' => $name,
        ]));
    }

    private function createBankAccounts(string $userId): array
    {
        $checking = BankAccount::factory()->create([
            'user_id' => $userId,
            'name' => 'Conta Corrente',
            'type' => BankAccountType::CHECKING,
            'balance' => 5000.00,
        ]);

        $savings = BankAccount::factory()->create([
            'user_id' => $userId,
            'name' => 'Poupança',
            'type' => BankAccountType::SAVINGS,
            'balance' => 15000.00,
        ]);

        return [$checking, $savings];
    }

    private function createCreditCard(string $userId, string $bankAccountId): CreditCard
    {
        return CreditCard::factory()->create([
            'user_id' => $userId,
            'name' => 'Cartão Principal',
            'bank_account_id' => $bankAccountId,
            'total_limit' => 8000.00,
            'used_limit' => 0.00,
            'billing_cycle_end_date' => 15,
            'due_date' => 20,
        ]);
    }

    private function seedSingleTransactions(
        string      $userId,
        Collection  $categories,
        BankAccount $checking,
        BankAccount $savings,
    ): void
    {
        $expenses = [
            ['name' => 'Supermercado', 'amount' => 450.00, 'days_ago' => 5, 'category' => 'Alimentação'],
            ['name' => 'Farmácia', 'amount' => 120.50, 'days_ago' => 10, 'category' => 'Saúde'],
            ['name' => 'Academia', 'amount' => 89.90, 'days_ago' => 20, 'category' => 'Saúde'],
            ['name' => 'Restaurante', 'amount' => 78.00, 'days_ago' => 3, 'category' => 'Alimentação'],
            ['name' => 'Combustível', 'amount' => 200.00, 'days_ago' => 7, 'category' => 'Transporte'],
            ['name' => 'Cinema', 'amount' => 55.00, 'days_ago' => 15, 'category' => 'Lazer'],
        ];

        foreach ($expenses as $data) {
            $this->createTransaction(
                userId: $userId,
                name: $data['name'],
                type: TransactionType::EXPENSE,
                amount: $data['amount'],
                paymentType: PaymentType::SINGLE,
                date: now()->subDays($data['days_ago']),
                categoryId: $categories->firstWhere('name', $data['category'])?->id,
                billableType: BankAccount::class,
                billableId: $checking->id,
            );
        }

        $revenues = [
            ['name' => 'Salário', 'amount' => 7500.00, 'days_ago' => 2, 'category' => 'Salário'],
            ['name' => 'Freelance Design', 'amount' => 1200.00, 'days_ago' => 12, 'category' => 'Freelance'],
            ['name' => 'Rendimento Poupança', 'amount' => 85.00, 'days_ago' => 1, 'category' => 'Salário'],
        ];

        foreach ($revenues as $data) {
            $this->createTransaction(
                userId: $userId,
                name: $data['name'],
                type: TransactionType::REVENUE,
                amount: $data['amount'],
                paymentType: PaymentType::SINGLE,
                date: now()->subDays($data['days_ago']),
                categoryId: $categories->firstWhere('name', $data['category'])?->id,
                billableType: BankAccount::class,
                billableId: $savings->id,
            );
        }
    }

    private function seedInstallmentTransactions(
        string     $userId,
        Collection $categories,
        CreditCard $creditCard,
    ): void
    {
        $installments = [
            ['name' => 'Notebook', 'amount' => 4500.00, 'count' => 12, 'days_ago' => 45, 'category' => 'Lazer'],
            ['name' => 'Geladeira', 'amount' => 2800.00, 'count' => 10, 'days_ago' => 60, 'category' => 'Moradia'],
            ['name' => 'Curso Online', 'amount' => 600.00, 'count' => 3, 'days_ago' => 20, 'category' => 'Lazer'],
        ];

        foreach ($installments as $data) {
            $this->createTransaction(
                userId: $userId,
                name: $data['name'],
                type: TransactionType::EXPENSE,
                amount: $data['amount'],
                paymentType: PaymentType::INSTALLMENTS,
                date: now()->subDays($data['days_ago']),
                categoryId: $categories->firstWhere('name', $data['category'])?->id,
                billableType: CreditCard::class,
                billableId: $creditCard->id,
                paymentsCount: $data['count'],
            );
        }
    }

    private function seedRecurringTransactions(
        string      $userId,
        Collection  $categories,
        BankAccount $checking,
        BankAccount $savings,
    ): void
    {
        $monthlyExpenses = [
            ['name' => 'Aluguel', 'amount' => 2200.00, 'day' => 5, 'months_ago' => 6, 'category' => 'Moradia'],
            ['name' => 'Internet', 'amount' => 99.90, 'day' => 10, 'months_ago' => 12, 'category' => 'Moradia'],
            ['name' => 'Streaming', 'amount' => 39.90, 'day' => 15, 'months_ago' => 8, 'category' => 'Lazer'],
            ['name' => 'Plano de Saúde', 'amount' => 350.00, 'day' => 20, 'months_ago' => 24, 'category' => 'Saúde'],
        ];

        foreach ($monthlyExpenses as $data) {
            $date = now()->subMonths($data['months_ago'])->startOfMonth()->setDay($data['day']);

            $this->createTransaction(
                userId: $userId,
                name: $data['name'],
                type: TransactionType::EXPENSE,
                amount: $data['amount'],
                paymentType: PaymentType::RECURRENT,
                date: $date,
                categoryId: $categories->firstWhere('name', $data['category'])?->id,
                billableType: BankAccount::class,
                billableId: $checking->id,
                recurrencyType: RecurrencyType::MONTHLY,
                recurringDay: $data['day'],
            );
        }

        $yearlyExpenses = [
            ['name' => 'IPVA', 'amount' => 1800.00, 'day' => 15, 'month' => Month::MARCH, 'years_ago' => 2, 'category' => 'Transporte'],
            ['name' => 'Seguro Residencial', 'amount' => 650.00, 'day' => 10, 'month' => Month::JULY, 'years_ago' => 3, 'category' => 'Moradia'],
        ];

        foreach ($yearlyExpenses as $data) {
            $date = Carbon::createFromDate(
                now()->subYears($data['years_ago'])->year,
                $data['month']->value,
                $data['day'],
            );

            $this->createTransaction(
                userId: $userId,
                name: $data['name'],
                type: TransactionType::EXPENSE,
                amount: $data['amount'],
                paymentType: PaymentType::RECURRENT,
                date: $date,
                categoryId: $categories->firstWhere('name', $data['category'])?->id,
                billableType: BankAccount::class,
                billableId: $savings->id,
                recurrencyType: RecurrencyType::YEARLY,
                recurringDay: $data['day'],
                recurringMonth: $data['month'],
            );
        }
    }

    private function createTransaction(
        string          $userId,
        string          $name,
        TransactionType $type,
        float           $amount,
        PaymentType     $paymentType,
        Carbon          $date,
        ?string         $categoryId = null,
        ?string         $billableType = null,
        ?string         $billableId = null,
        ?RecurrencyType $recurrencyType = null,
        ?int            $recurringDay = null,
        ?Month          $recurringMonth = null,
        int             $paymentsCount = 1,
    ): Transaction
    {
        $transaction = Transaction::factory()->create([
            'user_id' => $userId,
            'name' => $name,
            'transaction_type' => $type,
            'total_amount' => $amount,
            'payment_type' => $paymentType,
            'transaction_date' => $paymentType !== PaymentType::RECURRENT ? $date->toDateString() : null,
            'recurrency_type' => $recurrencyType,
            'recurring_day' => $recurringDay,
            'recurring_month' => $recurringMonth?->value,
            'transaction_category_id' => $categoryId,
            'billable_type' => $billableType,
            'billable_id' => $billableId,
        ]);

        $dto = new TransactionDTO(
            name: $name,
            transactionType: $type,
            transactionCategory: null,
            notes: null,
            paymentType: $paymentType,
            totalAmount: $amount,
            recurrencyType: $recurrencyType,
            transactionDate: $date,
            recurringDay: $recurringDay,
            recurringMonth: $recurringMonth,
            paymentsCount: $paymentsCount,
            billableType: $billableType,
            billableId: $billableId,
        );

        $payments = $this->transactionsService->generatePayments($dto);

        $payments->each(function (Payment $payment) use ($transaction, $userId): void {
            $payment->transaction_id = $transaction->id;
            $payment->user_id = $userId;
            $payment->save();
        });

        return $transaction;
    }
}
