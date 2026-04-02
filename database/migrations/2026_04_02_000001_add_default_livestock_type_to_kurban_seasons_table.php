<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kurban_seasons', function (Blueprint $table): void {
            $table->string('default_livestock_type', 20)->default('large')->after('price_eur');
        });
    }

    public function down(): void
    {
        Schema::table('kurban_seasons', function (Blueprint $table): void {
            $table->dropColumn('default_livestock_type');
        });
    }
};
