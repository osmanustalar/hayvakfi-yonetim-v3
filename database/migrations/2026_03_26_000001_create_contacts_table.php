<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable()->index();
            $table->string('national_id', 20)->nullable()->index();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->boolean('is_donor')->default(false);
            $table->boolean('is_aid_recipient')->default(false);
            $table->boolean('is_student')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
