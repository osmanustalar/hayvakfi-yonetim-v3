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

    public function getBySafe(int $safeId, array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->where('safe_id', $safeId)
            ->orderBy('process_date', 'desc');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('process_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('process_date', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    public function getForReport(array $filters = []): Collection
    {
        $query = $this->model->newQuery()
            ->where('is_show', true)
            ->orderBy('process_date', 'desc');

        if (!empty($filters['safe_id'])) {
            $query->where('safe_id', $filters['safe_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('process_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('process_date', '<=', $filters['date_to']);
        }

        return $query->get();
    }
}
