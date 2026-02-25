<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 11);
            $table->string('status', 40)->default('PENDING')->index();
            $table->date('billing_date')->nullable();
            $table->unsignedSmallInteger('payment_number');
            $table->nullableMorphs('billable');
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
