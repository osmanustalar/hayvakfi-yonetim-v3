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
        Schema::table('kurban_groups', function (Blueprint $table) {
            $table->unsignedInteger('group_no')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kurban_groups', function (Blueprint $table) {
            $table->unsignedInteger('group_no')->nullable(false)->change();
        });
    }
};
