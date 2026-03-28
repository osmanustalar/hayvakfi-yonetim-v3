<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

class CompanyRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Company());
    }

    public function allActive(): Collection
    {
        return Company::where('is_active', true)->orderBy('name')->get();
    }

    public function findByUserId(int $userId): Collection
    {
        return Company::whereHas('users', fn ($q) => $q->where('users.id', $userId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
