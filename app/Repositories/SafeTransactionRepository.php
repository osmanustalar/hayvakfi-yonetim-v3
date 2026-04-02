<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\SafeTransaction;
use Illuminate\Database\Eloquent\Collection;

class SafeTransactionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new SafeTransaction);
    }

    public function findBySafeAndIntegrationId(int $safeId, string $integrationId): ?SafeTransaction
    {
        /** @var SafeTransaction|null */
        return $this->model->newQuery()
            ->where('safe_id', $safeId)
            ->where('integration_id', $integrationId)
            ->first();
    }

    /**
     * Belirli bir kasadaki, belirli tarihteki, "ATAMA BEKLİYOR" kategorisinde, henüz atanmamış işlemleri getir.
     *
     * @return Collection<int, SafeTransaction>
     */
    public function getUnassignedBySafeAndDate(int $safeId, string $processDate): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScopes()
            ->where('safe_id', $safeId)
            ->where('process_date', $processDate)
            ->whereNull('target_transaction_id')
            ->whereHas('items', fn ($q) => $q->where('transaction_category_id', 3))
            ->with('safe.currency')
            ->get();
    }

    /**
     * ATAMA BEKLİYOR kategorisindeki işlemler için uygun eşleşmeleri getir.
     * Transfer: transaction_date eşleşmeli + tutar aynı olmalı
     * Exchange: transaction_date eşleşmeli + tutar farklı olabilir
     *
     * @param  SafeTransaction  $source  Kaynak işlem
     * @param  Safe  $targetSafe  Hedef kasa
     * @param  string  $operationType  'transfer' veya 'exchange'
     * @return Collection<int, SafeTransaction>
     */
    public function getEligibleTransactions(
        SafeTransaction $source,
        Safe $targetSafe,
        string $operationType
    ): Collection {
        // transaction_date eşleştir (banka zaman damgası)
        $baseQuery = SafeTransaction::withoutGlobalScopes()
            ->where('safe_id', $targetSafe->id)
            ->where('transaction_date', $source->transaction_date)
            ->whereNull('target_transaction_id')
            ->whereHas('items', fn ($q) => $q->where('transaction_category_id', 3));

        if ($operationType === 'transfer') {
            // Transfer: TUTAR AYNI olmak zorunlu
            return $baseQuery->where('total_amount', $source->total_amount)->get();
        } else {
            // Döviz: tüm kayıtlar (tutar farklı olabilir)
            return $baseQuery->get();
        }
    }
}
