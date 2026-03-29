<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SafeTransaction;
use Illuminate\Database\Eloquent\Collection;

class SafeTransactionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SafeTransaction());
    }

    public function findBySafeAndIntegrationId(int $safeId, string $integrationId): ?SafeTransaction
    {
        /** @var SafeTransaction|null */
        return $this->model->newQuery()
            ->where('safe_id', $safeId)
            ->where('integration_id', $integrationId)
            ->first();
    }
}
