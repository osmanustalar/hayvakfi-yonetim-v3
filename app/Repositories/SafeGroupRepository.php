<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SafeGroup;
use Illuminate\Database\Eloquent\Collection;

class SafeGroupRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SafeGroup());
    }

    public function allActive(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function withSafes(): Collection
    {
        return $this->model->newQuery()
            ->with('safes')
            ->get();
    }
}
