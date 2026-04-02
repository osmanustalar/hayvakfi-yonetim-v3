<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('safe_transactions', function (Blueprint $table) {
            $table->foreignId('kurban_list_id')->nullable()->constrained('kurban_lists')->nullOnDelete();
            $table->integer('share_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safe_transactions', function (Blueprint $table) {
            $table->dropForeign(['kurban_list_id']);
            $table->dropColumn('kurban_list_id');
            $table->dropColumn('share_count');
        });
    }
};
