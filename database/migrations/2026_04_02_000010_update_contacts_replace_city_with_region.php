<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropColumn('city');
            $table->foreignId('region_id')
                ->nullable()
                ->after('address')
                ->constrained('regions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropForeign(['region_id']);
            $table->dropColumn('region_id');
            $table->string('city', 100)->nullable()->after('address');
        });
    }
};
