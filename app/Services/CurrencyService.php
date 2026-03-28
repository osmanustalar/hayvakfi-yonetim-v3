<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use App\Repositories\CurrencyRepository;
use Illuminate\Database\Eloquent\Collection;

class CurrencyService
{
    public function __construct(private readonly CurrencyRepository $repository) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function allActive(): Collection
    {
        return $this->repository->allActive();
    }

    public function create(array $data): Currency
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
}
