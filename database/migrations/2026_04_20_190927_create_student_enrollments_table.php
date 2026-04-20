<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->date('enrollment_date');
            $table->decimal('monthly_fee', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['class_id', 'contact_id']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
