<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->decimal('total_limit', 11);
            $table->decimal('used_limit', 11);
            $table->unsignedTinyInteger('billing_cycle_end_date');
            $table->unsignedTinyInteger('due_date');
            $table->string('color_palette')->nullable();
            $table->string('color')->nullable();
            $table->foreignUlid('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};
