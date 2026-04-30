<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_contact_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['contact_id', 'contact_category_id'], 'contact_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_contact_category');
    }
};
