<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Safe;
use App\Repositories\SafeGroupRepository;
use App\Repositories\SafeRepository;
use Illuminate\Database\Eloquent\Collection;

class SafeService
{
    public function __construct(
        private readonly SafeRepository $repository,
        private readonly SafeGroupRepository $groupRepository,
    ) {}

    public function allActive(): Collection
    {
        return $this->repository->allActive();
    }

    public function find(int $id): ?Safe
    {
        /** @var Safe|null */
        return $this->repository->find($id);
    }

    public function create(array $data): Safe
    {
        $data['created_user_id'] = auth()->id();
        $data['company_id'] = session('active_company_id');

        /** @var Safe */
        return $this->repository->create($data);
    }

    public function update(Safe $safe, array $data): Safe
    {
        $this->repository->update($safe->id, $data);

        /** @var Safe */
        return $safe->fresh();
    }

    public function delete(Safe $safe): bool
    {
        return $this->repository->delete($safe->id);
    }

    /**
     * lockForUpdate ile bakiyeyi günceller.
     * MUTLAKA DB::transaction() içinde çağrılmalıdır.
     */
    public function updateBalance(Safe $safe, string $type, float $amount): void
    {
        $locked = $this->repository->findWithLock($safe->id);

        if ($type === TransactionType::INCOME->value) {
            $locked->increment('balance', $amount);
        } else {
            $this->checkBalance($locked, $amount);
            $locked->decrement('balance', $amount);
        }
    }

    /**
     * Bakiye yeterliliği kontrolü. Yetersizse exception.
     */
    public function checkBalance(Safe $safe, float $amount): void
    {
        if ((float) $safe->balance < $amount) {
            throw new InsufficientBalanceException(
                "Yetersiz bakiye. Mevcut: {$safe->balance}, Gereken: {$amount}"
            );
        }
    }
}
