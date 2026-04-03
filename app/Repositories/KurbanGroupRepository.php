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
        $group = KurbanGroup::findOrFail($id);
        
        // Grup numarasını boşa çıkar ki başkası alabilsin
        $group->group_no = null;
        $group->save();

        return $group->delete();
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
     * Sezon için bir sonraki veya aradaki ilk boş grup numarasını hesapla.
     */
    public function nextGroupNo(int $companyId, int $seasonId): int
    {
        $existing = KurbanGroup::where('company_id', $companyId)
            ->where('kurban_season_id', $seasonId)
            ->whereNotNull('group_no')
            ->pluck('group_no')
            ->sort()
            ->toArray();

        $next = 1;
        foreach ($existing as $no) {
            if ($no > $next) {
                return $next;
            }
            if ($no == $next) {
                $next++;
            }
        }

        return $next;
    }
}
