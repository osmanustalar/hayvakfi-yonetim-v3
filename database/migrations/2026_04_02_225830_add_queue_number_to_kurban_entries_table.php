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
        Schema::table('kurban_entries', function (Blueprint $table) {
            $table->integer('queue_number')->nullable()->after('kurban_list_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kurban_entries', function (Blueprint $table) {
            $table->dropColumn('queue_number');
        });
    }
};
