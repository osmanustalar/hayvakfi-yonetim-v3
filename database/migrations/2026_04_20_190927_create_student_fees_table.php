<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_id')->constrained('student_enrollments')->onDelete('cascade');
            $table->date('period');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('payment_transaction_id')->nullable()->constrained('safe_transactions')->onDelete('set null');
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['enrollment_id', 'period']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fees');
    }
};
