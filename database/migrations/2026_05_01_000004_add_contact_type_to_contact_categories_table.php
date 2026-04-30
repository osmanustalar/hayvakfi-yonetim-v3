<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_categories', function (Blueprint $table): void {
            // null: genel kategori — donor/aid_recipient/student: sistem davranışı tetikler
            $table->string('contact_type', 30)->nullable()->unique()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('contact_categories', function (Blueprint $table): void {
            $table->dropColumn('contact_type');
        });
    }
};
