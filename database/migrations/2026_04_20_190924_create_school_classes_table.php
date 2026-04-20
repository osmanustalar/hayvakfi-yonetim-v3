<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('default_monthly_fee', 10, 2);
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
