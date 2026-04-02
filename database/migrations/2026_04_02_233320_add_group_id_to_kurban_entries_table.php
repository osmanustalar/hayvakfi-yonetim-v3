<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kurban_entries', function (Blueprint $table) {
            // Kolon zaten varsa atla, yoksa oluştur
            if (! Schema::hasColumn('kurban_entries', 'kurban_group_id')) {
                $table->foreignId('kurban_group_id')
                    ->nullable()
                    ->after('kurban_list_id')
                    ->constrained()
                    ->nullOnDelete();
            } else {
                // Kolon var ama FK kısıtlaması olmayabilir, ekle
                $table->foreignId('kurban_group_id')
                    ->nullable()
                    ->change();
                $table->foreign('kurban_group_id')
                    ->references('id')
                    ->on('kurban_groups')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('kurban_entries', function (Blueprint $table) {
            $table->dropForeign(['kurban_group_id']);
            $table->dropColumn('kurban_group_id');
        });
    }
};
