<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contact_phones', function (Blueprint $table): void {
            // Önce normal index'i kaldır, sonra unique ekle
            $table->dropIndex(['phone']);
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('contact_phones', function (Blueprint $table): void {
            $table->dropUnique(['phone']);
            $table->index('phone');
        });
    }
};
