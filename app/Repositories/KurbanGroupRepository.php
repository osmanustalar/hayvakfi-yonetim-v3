<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\KurbanGroup;

class KurbanGroupRepository
{
    public function create(array $data): KurbanGroup
    {
        return KurbanGroup::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return KurbanGroup::where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return KurbanGroup::findOrFail($id)->delete();
    }

    /**
     * Aynı liste içinde kapasitesi dolu olmayan ilk grubu bul.
     */
    public function findAvailableGroupInList(int $kurbanListId): ?KurbanGroup
    {
        return KurbanGroup::whereHas('entries', fn ($q) => $q->where('kurban_list_id', $kurbanListId))
            ->orWhereDoesntHave('entries')
            ->withCount('entries')
            ->having('entries_count', '<', KurbanGroup::MAX_MEMBERS)
            ->whereHas('season', function ($q) use ($kurbanListId) {
                $q->whereHas('lists', fn ($lq) => $lq->where('id', $kurbanListId));
            })
            ->orderBy('group_no')
            ->first();
    }

    /**
     * Sezon için bir sonraki grup numarasını hesapla.
     */
    public function nextGroupNo(int $companyId, int $seasonId): int
    {
        $max = KurbanGroup::where('company_id', $companyId)
            ->where('kurban_season_id', $seasonId)
            ->max('group_no');

        return ($max ?? 0) + 1;
    }
}
