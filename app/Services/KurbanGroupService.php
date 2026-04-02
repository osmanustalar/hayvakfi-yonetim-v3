<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LivestockType;
use App\Models\KurbanEntry;
use App\Models\KurbanGroup;
use App\Models\KurbanList;
use App\Repositories\KurbanGroupRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KurbanGroupService
{
    public function __construct(
        private readonly KurbanGroupRepository $repository,
    ) {}

    /**
     * Verilen büyük baş kaydını uygun gruba atar (sadece aynı liste içinde).
     * Aynı listede kapasiteli grup yoksa yeni grup oluşturur.
     */
    public function assignToGroup(KurbanEntry $entry): void
    {
        if ($entry->livestock_type !== LivestockType::LARGE) {
            return; // Küçük baş gruba girmez
        }

        DB::transaction(function () use ($entry): void {
            $list = KurbanList::findOrFail($entry->kurban_list_id);
            $companyId = $entry->company_id;
            $seasonId = $list->kurban_season_id;

            // Aynı listede, 7'den az üyesi olan grup ara
            $group = KurbanGroup::where('kurban_season_id', $seasonId)
                ->where('company_id', $companyId)
                ->withCount('entries')
                ->having('entries_count', '<', KurbanGroup::MAX_MEMBERS)
                ->whereHas('entries', fn ($q) => $q->where('kurban_list_id', $entry->kurban_list_id))
                ->orderBy('group_no')
                ->lockForUpdate()
                ->first();

            // Yoksa yeni grup oluştur
            if ($group === null) {
                $group = $this->createGroup($companyId, $seasonId);
            }

            $entry->update(['kurban_group_id' => $group->id]);
        });
    }

    /**
     * Kaydı farklı bir gruba taşır. 7 kişi limitini kontrol eder.
     *
     * @throws RuntimeException hedef grup dolu ise
     */
    public function moveToGroup(KurbanEntry $entry, KurbanGroup $targetGroup): void
    {
        DB::transaction(function () use ($entry, $targetGroup): void {
            // Lock: hedef grup üye sayısını kontrol et
            $fresh = KurbanGroup::where('id', $targetGroup->id)
                ->withCount('entries')
                ->lockForUpdate()
                ->firstOrFail();

            if ($fresh->entries_count >= KurbanGroup::MAX_MEMBERS) {
                throw new RuntimeException(
                    "Grup #{$targetGroup->group_no} doldu (max " . KurbanGroup::MAX_MEMBERS . " kişi)."
                );
            }

            $entry->update(['kurban_group_id' => $targetGroup->id]);
        });
    }

    /**
     * Yeni boş bir grup oluşturur.
     */
    public function createGroup(int $companyId, int $seasonId): KurbanGroup
    {
        $groupNo = $this->repository->nextGroupNo($companyId, $seasonId);

        return $this->repository->create([
            'company_id' => $companyId,
            'kurban_season_id' => $seasonId,
            'group_no' => $groupNo,
            'created_user_id' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Boş grubu siler. İçinde kayıt varsa silinemez.
     *
     * @throws RuntimeException grup dolu ise
     */
    public function deleteGroup(KurbanGroup $group): void
    {
        if ($group->entries()->exists()) {
            throw new RuntimeException("Grup #{$group->group_no} içinde kayıt var, silinemez.");
        }

        $this->repository->delete($group->id);
    }
}
