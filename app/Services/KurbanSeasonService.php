<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\KurbanSeason;
use App\Repositories\KurbanSeasonRepository;

class KurbanSeasonService
{
    public function __construct(
        private readonly KurbanSeasonRepository $repository
    ) {}

    public function create(array $data): KurbanSeason
    {
        $data['company_id']      = (int) session('active_company_id');
        $data['created_user_id'] = auth()->id();

        /** @var KurbanSeason */
        $season = $this->repository->create($data);

        return $season;
    }

    public function update(KurbanSeason $season, array $data): KurbanSeason
    {
        $this->repository->update($season->id, $data);

        return $season->refresh();
    }

    public function delete(KurbanSeason $season): bool
    {
        return $this->repository->delete($season->id);
    }
}
