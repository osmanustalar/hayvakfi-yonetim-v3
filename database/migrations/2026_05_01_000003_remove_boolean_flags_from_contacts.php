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
        // contact_type kolonu bu migration sırasında henüz eklenmiş olmayabilir (000004 sonra çalışır).
        // Bu yüzden kategori adına göre pivot verilerini kopyalıyoruz.
        // Kategori adları ContactCategorySeeder tarafından sabitlenmiştir.
        $now = now();

        $mapping = [
            'Bağışçı'    => 'is_donor',
            'Yardım Alan' => 'is_aid_recipient',
            'Öğrenci'    => 'is_student',
        ];

        foreach ($mapping as $categoryName => $column) {
            $category = DB::table('contact_categories')
                ->where('name', $categoryName)
                ->whereNull('deleted_at')
                ->first();

            if ($category === null) {
                continue;
            }

            DB::table('contacts')
                ->whereNull('deleted_at')
                ->where($column, true)
                ->pluck('id')
                ->each(fn (int $id) => DB::table('contact_contact_category')
                    ->insertOrIgnore([
                        'contact_id'          => $id,
                        'contact_category_id' => $category->id,
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ])
                );
        }

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropColumn(['is_donor', 'is_aid_recipient', 'is_student']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->boolean('is_donor')->default(false)->after('city');
            $table->boolean('is_aid_recipient')->default(false)->after('is_donor');
            $table->boolean('is_student')->default(false)->after('is_aid_recipient');
        });
    }
};
