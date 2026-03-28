<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Safe;
use Illuminate\Database\Eloquent\Collection;

class SafeRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Safe());
    }

    public function allActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function findWithLock(int $id): Safe
    {
        /** @var Safe */
        return $this->model->newQuery()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function findByGroup(int $safeGroupId): Collection
    {
        return $this->model->newQuery()
            ->where('safe_group_id', $safeGroupId)
            ->get();
    }
}
