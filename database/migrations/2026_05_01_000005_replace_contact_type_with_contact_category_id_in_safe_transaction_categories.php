<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safe_transaction_categories', function (Blueprint $table): void {
            $table->foreignId('contact_category_id')
                ->nullable()
                ->after('contact_type')
                ->constrained('contact_categories')
                ->nullOnDelete();
        });

        // Mevcut contact_type değerlerini contact_category_id'ye taşı
        $typeMap = [
            'donor'         => 'Bağışçı',
            'aid_recipient' => 'Yardım Alan',
            'student'       => 'Öğrenci',
        ];

        foreach ($typeMap as $typeValue => $categoryName) {
            $category = DB::table('contact_categories')
                ->where('name', $categoryName)
                ->whereNull('deleted_at')
                ->first();

            if ($category === null) {
                continue;
            }

            DB::table('safe_transaction_categories')
                ->where('contact_type', $typeValue)
                ->whereNull('deleted_at')
                ->update(['contact_category_id' => $category->id]);
        }

        Schema::table('safe_transaction_categories', function (Blueprint $table): void {
            $table->dropColumn('contact_type');
        });
    }

    public function down(): void
    {
        Schema::table('safe_transaction_categories', function (Blueprint $table): void {
            $table->string('contact_type', 30)->nullable()->after('contact_category_id');
        });

        Schema::table('safe_transaction_categories', function (Blueprint $table): void {
            $table->dropForeign(['contact_category_id']);
            $table->dropColumn('contact_category_id');
        });
    }
};
