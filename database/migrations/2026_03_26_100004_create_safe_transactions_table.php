<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safe_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('safe_id')->constrained('safes')->restrictOnDelete();
            $table->string('type', 20);
            $table->string('operation_type', 20)->nullable();
            $table->decimal('total_amount', 15, 4);
            $table->decimal('amount', 15, 4);
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->decimal('item_rate', 10, 4)->nullable();
            $table->foreignId('target_safe_id')->nullable()->constrained('safes')->nullOnDelete();
            $table->foreignId('target_transaction_id')
                ->nullable()
                ->constrained('safe_transactions')
                ->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('reference_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('process_date');
            $table->dateTime('transaction_date')->nullable();
            $table->decimal('balance_after_created', 15, 4)->default(0);
            $table->string('integration_id')->nullable();
            $table->string('import_file')->nullable();
            $table->boolean('is_show')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // MySQL'de NULL değerler unique kısıtını aşmaz — istenen davranış
            $table->unique(['safe_id', 'integration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safe_transactions');
    }
};
