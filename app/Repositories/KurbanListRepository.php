<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\KurbanList;
use Illuminate\Database\Eloquent\Collection;

class KurbanListRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new KurbanList);
    }

    /**
     * @return Collection<int, KurbanList>
     */
    public function findBySeason(int $seasonId): Collection
    {
        return $this->model->newQuery()
            ->where('kurban_season_id', $seasonId)
            ->with(['season', 'collector'])
            ->orderBy('name')
            ->get();
    }
}
