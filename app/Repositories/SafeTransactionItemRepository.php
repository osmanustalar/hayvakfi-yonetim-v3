<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SafeTransactionItem;

class SafeTransactionItemRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SafeTransactionItem());
    }

    public function createMany(int $transactionId, int $companyId, array $items): void
    {
        foreach ($items as $item) {
            $this->model->newQuery()->create([
                'transaction_id'          => $transactionId,
                'company_id'              => $companyId,
                'transaction_category_id' => $item['transaction_category_id'],
                'donation_category_id'    => $item['donation_category_id'] ?? null,
                'amount'                  => $item['amount'],
            ]);
        }
    }

    public function sumByTransaction(int $transactionId): float
    {
        return (float) $this->model->newQuery()
            ->where('transaction_id', $transactionId)
            ->sum('amount');
    }
}
