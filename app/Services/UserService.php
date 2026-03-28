<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    public function __construct(private readonly UserRepository $repository) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(int $id): ?User
    {
        return $this->repository->find($id);
    }

    public function create(array $data): User
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

    public function attachCompany(int $userId, int $companyId): void
    {
        $user = $this->repository->find($userId);
        $user->companies()->syncWithoutDetaching([$companyId]);
    }

    public function detachCompany(int $userId, int $companyId): void
    {
        $user = $this->repository->find($userId);
        $user->companies()->detach($companyId);
    }
}
