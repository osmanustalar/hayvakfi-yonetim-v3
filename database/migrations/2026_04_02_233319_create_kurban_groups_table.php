<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurban_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kurban_season_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('group_no');
            $table->text('notes')->nullable();
            $table->foreignId('created_user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'kurban_season_id', 'group_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kurban_groups');
    }
};
