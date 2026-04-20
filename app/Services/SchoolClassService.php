<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SchoolClass;
use App\Repositories\SchoolClassRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SchoolClassService
{
    public function __construct(
        private readonly SchoolClassRepository $repository
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    public function getAllActive(): Collection
    {
        return $this->repository->getAllActive();
    }

    public function find(int $id): ?SchoolClass
    {
        return $this->repository->find($id);
    }

    public function create(array $data): SchoolClass
    {
        return DB::transaction(function () use ($data): SchoolClass {
            $data['company_id'] = (int) session('active_company_id');

            return $this->repository->create($data);
        });
    }

    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data): bool {
            return $this->repository->update($id, $data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->repository->delete($id);
        });
    }
}
