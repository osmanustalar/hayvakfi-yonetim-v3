<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurban_groups', function (Blueprint $table): void {
            $table->string('code', 20)->nullable()->after('group_no');
            $table->string('logo1')->nullable()->after('code');
            $table->string('logo2')->nullable()->after('logo1');
        });
    }

    public function down(): void
    {
        Schema::table('kurban_groups', function (Blueprint $table): void {
            $table->dropColumn(['code', 'logo1', 'logo2']);
        });
    }
};
