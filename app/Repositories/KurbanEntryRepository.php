<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\KurbanEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class KurbanEntryRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new KurbanEntry());
    }

    /**
     * @return Collection<int, KurbanEntry>
     */
    public function findByList(int $listId): Collection
    {
        return $this->model->newQuery()
            ->where('kurban_list_id', $listId)
            ->with(['contact', 'currency', 'safeTransaction'])
            ->get();
    }

    /**
     * @return Collection<int, KurbanEntry>
     */
    public function findUnpaidByList(int $listId): Collection
    {
        return $this->model->newQuery()
            ->where('kurban_list_id', $listId)
            ->where('is_paid', false)
            ->get();
    }

    /**
     * @return array{total_count: int, paid_count: int, unpaid_count: int}
     */
    public function getStatsByList(int $listId): array
    {
        $result = $this->model->newQuery()
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN is_paid = 0 THEN 1 ELSE 0 END) as unpaid_count
            ')
            ->where('kurban_list_id', $listId)
            ->first();

        return [
            'total_count' => (int) $result->total_count,
            'paid_count' => (int) $result->paid_count,
            'unpaid_count' => (int) $result->unpaid_count,
        ];
    }

    /**
     * @return array{total_count: int, paid_count: int, unpaid_count: int}
     */
    public function getStatsBySeason(int $seasonId): array
    {
        $result = $this->model->newQuery()
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN is_paid = 0 THEN 1 ELSE 0 END) as unpaid_count
            ')
            ->join('kurban_lists', 'kurban_entries.kurban_list_id', '=', 'kurban_lists.id')
            ->where('kurban_lists.kurban_season_id', $seasonId)
            ->first();

        return [
            'total_count' => (int) $result->total_count,
            'paid_count' => (int) $result->paid_count,
            'unpaid_count' => (int) $result->unpaid_count,
        ];
    }
}
