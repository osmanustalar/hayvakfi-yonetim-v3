<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SafeGroup;
use App\Repositories\SafeGroupRepository;
use Illuminate\Database\Eloquent\Collection;

class SafeGroupService
{
    public function __construct(
        private readonly SafeGroupRepository $repository,
    ) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function allActive(): Collection
    {
        return $this->repository->allActive();
    }

    public function find(int $id): ?SafeGroup
    {
        /** @var SafeGroup|null */
        return $this->repository->find($id);
    }

    public function create(array $data): SafeGroup
    {
        $data['created_user_id'] = auth()->id();
        $data['company_id']      = session('active_company_id');
        /** @var SafeGroup */
        return $this->repository->create($data);
    }

    public function update(SafeGroup $safeGroup, array $data): SafeGroup
    {
        $this->repository->update($safeGroup->id, $data);
        /** @var SafeGroup */
        return $safeGroup->fresh();
    }

    public function delete(SafeGroup $safeGroup): bool
    {
        return $this->repository->delete($safeGroup->id);
    }
}
