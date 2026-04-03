<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Her sezon için kurban kayıtlarına sıra numarası ata.
     * Oluşturulma tarihi sırasına göre 1'den başlayarak numaralandırır.
     */
    public function up(): void
    {
        // Tüm sezondaki liste ID'lerini grupla
        $seasonLists = DB::table('kurban_lists')
            ->whereNull('deleted_at')
            ->select('id', 'kurban_season_id')
            ->get()
            ->groupBy('kurban_season_id');

        foreach ($seasonLists as $seasonId => $lists) {
            $listIds = $lists->pluck('id')->toArray();

            // Sezondaki tüm aktif kayıtları oluşturulma tarihine göre sırala
            $entries = DB::table('kurban_entries')
                ->whereIn('kurban_list_id', $listIds)
                ->whereNull('deleted_at')
                ->orderBy('created_at')
                ->orderBy('id')
                ->pluck('id');

            $queueNumber = 1;
            foreach ($entries as $entryId) {
                DB::table('kurban_entries')
                    ->where('id', $entryId)
                    ->update(['queue_number' => $queueNumber++]);
            }
        }
    }

    public function down(): void
    {
        DB::table('kurban_entries')->update(['queue_number' => null]);
    }
};
