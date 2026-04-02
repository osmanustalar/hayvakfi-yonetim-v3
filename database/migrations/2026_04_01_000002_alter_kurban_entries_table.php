<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tablo 2026_03_31_100003 migration'ında güncel yapıyla oluşturuldu.
        // sacrifice_category_id FK constraint'i eksikse ekle.
        Schema::table('kurban_entries', function (Blueprint $table): void {
            $table->foreign('sacrifice_category_id')
                ->references('id')
                ->on('safe_transaction_categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kurban_entries', function (Blueprint $table): void {
            // FK'yi kaldır
            $table->dropForeign(['sacrifice_category_id']);
            $table->dropColumn('sacrifice_category_id');

            // Eski kolonları geri ekle
            $table->decimal('amount', 15, 4)->after('notes');
            $table->foreignId('currency_id')
                ->after('amount')
                ->constrained('currencies')
                ->restrictOnDelete();
            $table->string('sacrifice_type', 50)->after('currency_id');
        });
    }
};
