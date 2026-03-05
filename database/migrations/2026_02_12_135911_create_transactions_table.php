<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('transaction_type', 40)->index(); // Expense, Revenue
            $table->decimal('total_amount', 11);

            // Transaction date (When there is no recurrency)
            $table->date('transaction_date')->nullable();

            $table->string('payment_type', 40)->index(); // Single, Installments, Recurrent
            $table->string('recurrency_type', 40)->nullable()->index(); // Monthly, Yearly

            // Transaction day/month (When there is a recurrency)
            $table->unsignedTinyInteger('recurring_day')->nullable();
            $table->unsignedTinyInteger('recurring_month')->nullable();

            $table->nullableMorphs('billable');
            $table->foreignId('transaction_category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('transactions');
    }
};
