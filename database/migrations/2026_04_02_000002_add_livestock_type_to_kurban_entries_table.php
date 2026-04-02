<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kurban_entries', function (Blueprint $table): void {
            $table->string('livestock_type', 20)->default('large')->after('sacrifice_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('kurban_entries', function (Blueprint $table): void {
            $table->dropColumn('livestock_type');
        });
    }
};
