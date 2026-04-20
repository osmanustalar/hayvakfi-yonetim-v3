<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aid_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained('safe_transactions')->onDelete('set null');
            $table->string('aid_type');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('given_at');
            $table->foreignId('created_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aid_records');
    }
};
