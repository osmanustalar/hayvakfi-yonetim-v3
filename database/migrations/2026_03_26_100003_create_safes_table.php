<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('safe_group_id')->constrained('safe_groups')->restrictOnDelete();
            $table->string('name');
            $table->string('iban', 34)->nullable();
            $table->foreignId('currency_id')->constrained('currencies')->restrictOnDelete();
            $table->decimal('balance', 15, 4)->default(0);
            $table->boolean('is_manual_transaction')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->dateTime('last_processed_at')->nullable();
            $table->string('integration_id')->nullable();
            $table->foreignId('created_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safes');
    }
};
