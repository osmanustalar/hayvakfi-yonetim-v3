<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aid_records', function (Blueprint $table) {
            $table->dropForeign(['created_user_id']);
            $table->foreign('created_user_id')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('aid_records', function (Blueprint $table) {
            $table->dropForeign(['created_user_id']);
            $table->foreign('created_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
