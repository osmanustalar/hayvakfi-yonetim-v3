<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('safe_transaction_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('transaction_id')
                  ->constrained('safe_transactions')
                  ->cascadeOnDelete();
            $table->foreignId('transaction_category_id')
                  ->constrained('safe_transaction_categories')
                  ->restrictOnDelete();
            $table->foreignId('donation_category_id')
                  ->nullable()
                  ->constrained('safe_transaction_categories')
                  ->nullOnDelete();
            $table->decimal('amount', 15, 4);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safe_transaction_items');
    }
};
