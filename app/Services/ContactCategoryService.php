<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ContactCategory;
use App\Repositories\ContactCategoryRepository;

class ContactCategoryService
{
    public function __construct(
        private readonly ContactCategoryRepository $repository
    ) {
    }

    public function create(array $data): ContactCategory
    {
        $data['created_user_id'] = auth()->id();

        return $this->repository->create($data);
    }

    public function update(ContactCategory $category, array $data): ContactCategory
    {
        $this->repository->update($category->id, $data);

        return $category->fresh();
    }

    public function delete(ContactCategory $category): void
    {
        $this->repository->delete($category->id);
    }
}
