<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\KurbanSeason;
use Illuminate\Database\Eloquent\Collection;

class KurbanSeasonRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new KurbanSeason());
    }

    /**
     * @return Collection<int, KurbanSeason>
     */
    public function getActiveSeasons(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('year', 'desc')
            ->get();
    }

    public function findByYear(int $year): ?KurbanSeason
    {
        /** @var KurbanSeason|null */
        return $this->model->newQuery()
            ->where('year', $year)
            ->first();
    }
}
