<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SafeTransactionCategory;
use App\Repositories\SafeTransactionCategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class SafeTransactionCategoryService
{
    public function __construct(
        private readonly SafeTransactionCategoryRepository $repository,
    ) {}

    public function getForActiveCompany(): Collection
    {
        return $this->repository->forActiveCompany();
    }

    public function getTree(): Collection
    {
        return $this->repository->forActiveCompanyWithChildren();
    }

    public function find(int $id): ?SafeTransactionCategory
    {
        /** @var SafeTransactionCategory|null */
        return $this->repository->find($id);
    }

    public function create(array $data): SafeTransactionCategory
    {
        $data['created_user_id'] = auth()->id();
        $data['company_id']      = session('active_company_id');
        /** @var SafeTransactionCategory */
        return $this->repository->create($data);
    }

    public function update(SafeTransactionCategory $category, array $data): SafeTransactionCategory
    {
        $this->repository->update($category->id, $data);
        /** @var SafeTransactionCategory */
        return $category->fresh();
    }

    public function delete(SafeTransactionCategory $category): bool
    {
        return $this->repository->delete($category->id);
    }
}
