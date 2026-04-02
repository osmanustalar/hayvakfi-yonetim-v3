<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurban_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('kurban_list_id')->constrained('kurban_lists')->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->foreignId('sacrifice_category_id')->constrained('safe_transaction_categories')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->date('paid_date')->nullable();
            $table->foreignId('safe_transaction_id')->nullable()->constrained('safe_transactions')->nullOnDelete();
            $table->foreignId('created_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'kurban_list_id', 'is_paid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurban_entries');
    }
};
