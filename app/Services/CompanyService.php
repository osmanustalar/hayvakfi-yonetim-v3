<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Repositories\CompanyRepository;
use Illuminate\Database\Eloquent\Collection;

class CompanyService
{
    public function __construct(private readonly CompanyRepository $repository) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function allActive(): Collection
    {
        return $this->repository->allActive();
    }

    public function find(int $id): ?Company
    {
        return $this->repository->find($id);
    }

    public function create(array $data): Company
    {
        return $this->repository->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function findByUserId(int $userId): Collection
    {
        return $this->repository->findByUserId($userId);
    }
}
