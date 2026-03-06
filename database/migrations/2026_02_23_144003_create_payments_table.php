<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->decimal('amount', 11);
            $table->decimal('paid_amount', 11)->nullable();
            $table->string('status', 40)->default('PENDING')->index();
            $table->date('billing_date');
            $table->date('payment_date')->nullable();
            $table->unsignedSmallInteger('payment_number');
            $table->nullableUlidMorphs('billable');
            $table->foreignUlid('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
