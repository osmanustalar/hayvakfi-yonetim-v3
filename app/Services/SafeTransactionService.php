<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationType;
use App\Enums\TransactionType;
use App\Exceptions\ManualTransactionNotAllowedException;
use App\Exceptions\SplitAmountMismatchException;
use App\Models\Safe;
use App\Models\SafeTransaction;
use App\Repositories\SafeRepository;
use App\Repositories\SafeTransactionItemRepository;
use App\Repositories\SafeTransactionRepository;
use Illuminate\Support\Facades\DB;

class SafeTransactionService
{
    public function __construct(
        private readonly SafeTransactionRepository     $repository,
        private readonly SafeTransactionItemRepository $itemRepository,
        private readonly SafeService                   $safeService,
        private readonly SafeRepository                $safeRepository,
    ) {}

    /**
     * Panelden manuel işlem girişi.
     * is_api_integration = true olan kasa grubunun kasaları manuel giriş kabul etmez.
     */
    public function create(array $data): SafeTransaction
    {
        $safe = Safe::with('safeGroup')->findOrFail($data['safe_id']);

        if ($safe->safeGroup->is_api_integration) {
            throw new ManualTransactionNotAllowedException(
                'Bu kasa grubu yalnızca API üzerinden beslenebilir, panelden işlem girilemez.'
            );
        }

        return $this->persist($data);
    }

    /**
     * API/entegrasyon işlemi.
     * Duplicate koruması: integration_id varsa önce kontrol eder (idempotent).
     */
    public function createFromApi(array $data): SafeTransaction
    {
        if (isset($data['integration_id'])) {
            $existing = $this->repository->findBySafeAndIntegrationId(
                $data['safe_id'],
                $data['integration_id']
            );
            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->persist($data);
    }

    /**
     * Kasalar arası transfer — atomik iki transaction.
     * Dönüş: ['source' => SafeTransaction, 'target' => SafeTransaction]
     *
     * @return array{source: SafeTransaction, target: SafeTransaction}
     */
    public function createTransfer(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $companyId   = (int) session('active_company_id');
            $createdById = auth()->id();

            $sourceSafe = $this->safeRepository->findWithLock($data['source_safe_id']);
            $this->safeService->checkBalance($sourceSafe, (float) $data['amount']);

            // Kaynak: gider
            /** @var SafeTransaction */
            $sourceTransaction = $this->repository->create([
                'company_id'            => $companyId,
                'safe_id'               => $sourceSafe->id,
                'type'                  => TransactionType::EXPENSE->value,
                'operation_type'        => OperationType::TRANSFER->value,
                'total_amount'          => $data['amount'],
                'target_safe_id'        => $data['target_safe_id'],
                'process_date'          => $data['process_date'],
                'description'           => $data['description'] ?? null,
                'reference_user_id'     => $data['reference_user_id'] ?? null,
                'created_user_id'       => $createdById,
                'balance_after_created' => 0,
            ]);

            $this->safeService->updateBalance($sourceSafe, TransactionType::EXPENSE->value, (float) $data['amount']);
            $sourceTransaction->update(['balance_after_created' => $sourceSafe->fresh()->balance]);

            $targetSafe = $this->safeRepository->findWithLock($data['target_safe_id']);

            // Hedef: gelir
            /** @var SafeTransaction */
            $targetTransaction = $this->repository->create([
                'company_id'            => $companyId,
                'safe_id'               => $targetSafe->id,
                'type'                  => TransactionType::INCOME->value,
                'operation_type'        => OperationType::TRANSFER->value,
                'total_amount'          => $data['amount'],
                'target_safe_id'        => $sourceSafe->id,
                'target_transaction_id' => $sourceTransaction->id,
                'process_date'          => $data['process_date'],
                'description'           => $data['description'] ?? null,
                'reference_user_id'     => $data['reference_user_id'] ?? null,
                'created_user_id'       => $createdById,
                'balance_after_created' => 0,
            ]);

            $this->safeService->updateBalance($targetSafe, TransactionType::INCOME->value, (float) $data['amount']);
            $targetTransaction->update(['balance_after_created' => $targetSafe->fresh()->balance]);

            // Çapraz referans
            $sourceTransaction->update(['target_transaction_id' => $targetTransaction->id]);

            // Kategori item'ları (ID: 1 = Hesaplar Arası Para Transferleri)
            $this->itemRepository->createMany($sourceTransaction->id, $companyId, [
                ['transaction_category_id' => 1, 'amount' => $data['amount']],
            ]);
            $this->itemRepository->createMany($targetTransaction->id, $companyId, [
                ['transaction_category_id' => 1, 'amount' => $data['amount']],
            ]);

            return ['source' => $sourceTransaction, 'target' => $targetTransaction];
        });
    }

    /**
     * Döviz işlemi (tek kasa, exchange_rate ile hesaplama).
     * total_amount = amount × exchange_rate (servis hesaplar, formdan alınmaz).
     */
    public function createExchange(array $data): SafeTransaction
    {
        $data['total_amount']   = round((float) $data['amount'] * (float) $data['exchange_rate'], 4);
        $data['operation_type'] = OperationType::EXCHANGE->value;

        // Döviz kategorisi ID 2 ile persist (items override)
        $data['items'] = [[
            'transaction_category_id' => 2,
            'amount'                  => $data['total_amount'],
        ]];

        // amount alanı tablodan kaldırıldığı için veriyi çıkar
        unset($data['amount']);

        return $this->persist($data);
    }

    /**
     * İki kasa arasında döviz transferi — atomik çift transaction.
     * Kaynak kasadan çıkış (source_amount), hedef kasaya giriş (target_amount).
     * Dönüş: ['source' => SafeTransaction, 'target' => SafeTransaction]
     *
     * @param array{
     *   source_safe_id: int,
     *   target_safe_id: int,
     *   source_amount: float,
     *   target_amount: float,
     *   item_rate: float,
     *   process_date: string,
     *   description: string|null,
     *   reference_user_id: int|null,
     * } $data
     * @return array{source: SafeTransaction, target: SafeTransaction}
     */
    public function createExchangeTransfer(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $companyId   = (int) session('active_company_id');
            $createdById = auth()->id();

            $sourceSafe = $this->safeRepository->findWithLock($data['source_safe_id']);
            $this->safeService->checkBalance($sourceSafe, (float) $data['source_amount']);

            // Kaynak: gider (çıkış kasa)
            /** @var SafeTransaction */
            $sourceTransaction = $this->repository->create([
                'company_id'            => $companyId,
                'safe_id'               => $sourceSafe->id,
                'type'                  => TransactionType::EXPENSE->value,
                'operation_type'        => OperationType::EXCHANGE->value,
                'total_amount'          => $data['source_amount'],
                'currency_id'           => $sourceSafe->currency_id,
                'item_rate'             => $data['item_rate'],
                'target_safe_id'        => $data['target_safe_id'],
                'process_date'          => $data['process_date'],
                'description'           => $data['description'] ?? null,
                'reference_user_id'     => $data['reference_user_id'] ?? null,
                'created_user_id'       => $createdById,
                'balance_after_created' => 0,
            ]);

            $sourceSafe->decrement('balance', $data['source_amount']);
            $sourceTransaction->update(['balance_after_created' => $sourceSafe->fresh()->balance]);

            $targetSafe = $this->safeRepository->findWithLock($data['target_safe_id']);

            // Hedef: gelir (giriş kasa)
            /** @var SafeTransaction */
            $targetTransaction = $this->repository->create([
                'company_id'            => $companyId,
                'safe_id'               => $targetSafe->id,
                'type'                  => TransactionType::INCOME->value,
                'operation_type'        => OperationType::EXCHANGE->value,
                'total_amount'          => $data['target_amount'],
                'currency_id'           => $targetSafe->currency_id,
                'item_rate'             => $data['item_rate'],
                'target_safe_id'        => $sourceSafe->id,
                'target_transaction_id' => $sourceTransaction->id,
                'process_date'          => $data['process_date'],
                'description'           => $data['description'] ?? null,
                'reference_user_id'     => $data['reference_user_id'] ?? null,
                'created_user_id'       => $createdById,
                'balance_after_created' => 0,
            ]);

            $targetSafe->increment('balance', $data['target_amount']);
            $targetTransaction->update(['balance_after_created' => $targetSafe->fresh()->balance]);

            // Çapraz referans
            $sourceTransaction->update(['target_transaction_id' => $targetTransaction->id]);

            // Kategori item'ları (ID: 2 = Döviz İşlemleri)
            $this->itemRepository->createMany($sourceTransaction->id, $companyId, [
                ['transaction_category_id' => 2, 'amount' => $data['source_amount']],
            ]);
            $this->itemRepository->createMany($targetTransaction->id, $companyId, [
                ['transaction_category_id' => 2, 'amount' => $data['target_amount']],
            ]);

            return ['source' => $sourceTransaction, 'target' => $targetTransaction];
        });
    }

    /**
     * Gelir/Gider işlemini güncelle.
     * Bakiye farkı hesaplanarak kasa bakiyesi düzeltilir.
     * balance_after_created değişmez (snapshot).
     */
    public function update(SafeTransaction $transaction, array $data): SafeTransaction
    {
        return DB::transaction(function () use ($transaction, $data): SafeTransaction {
            $safe    = $this->safeRepository->findWithLock($transaction->safe_id);
            $oldType = $transaction->type->value;
            $oldAmount  = (float) $transaction->total_amount;
            $newAmount  = (float) $data['total_amount'];
            $newType    = (string) $data['type'];
            $items      = $data['items'] ?? [];

            // Split doğrulama
            if (!empty($items)) {
                $itemsSum = collect($items)->sum(fn ($i) => (float) $i['amount']);
                if (abs($itemsSum - $newAmount) > 0.0001) {
                    throw new SplitAmountMismatchException(
                        "Kalem toplamı ({$itemsSum}) işlem tutarına ({$newAmount}) eşit olmalıdır."
                    );
                }
            }

            // Eski bakiyeyi geri al
            if ($oldType === TransactionType::INCOME->value) {
                $safe->decrement('balance', $oldAmount);
            } else {
                $safe->increment('balance', $oldAmount);
            }

            // Yeni tutarı uygula
            if ($newType === TransactionType::INCOME->value) {
                $safe->increment('balance', $newAmount);
            } else {
                $this->safeService->checkBalance($safe->fresh(), $newAmount);
                $safe->decrement('balance', $newAmount);
            }

            $updateData = array_diff_key($data, array_flip(['items']));
            $this->repository->update($transaction->id, $updateData);

            // Items güncelle
            if (!empty($items)) {
                $companyId = (int) session('active_company_id');
                // Mevcut items sil
                $transaction->items()->delete();
                $this->itemRepository->createMany($transaction->id, $companyId, $items);
            }

            /** @var SafeTransaction */
            return $transaction->fresh()->load('items');
        });
    }

    /**
     * Transfer çiftini güncelle — atomik.
     * source_transaction_id expense kaydıdır.
     */
    public function updateTransfer(SafeTransaction $sourceTransaction, array $data): array
    {
        return DB::transaction(function () use ($sourceTransaction, $data): array {
            /** @var SafeTransaction */
            $targetTransaction = SafeTransaction::withoutGlobalScopes()
                ->findOrFail($sourceTransaction->target_transaction_id);

            $sourceSafe = $this->safeRepository->findWithLock($sourceTransaction->safe_id);
            $targetSafe = $this->safeRepository->findWithLock($targetTransaction->safe_id);

            $oldAmount = (float) $sourceTransaction->total_amount;
            $newAmount = (float) $data['amount'];

            // Eski bakiyeleri geri al
            $sourceSafe->increment('balance', $oldAmount);
            $targetSafe->decrement('balance', $oldAmount);

            // Yeni tutarı uygula
            $this->safeService->checkBalance($sourceSafe->fresh(), $newAmount);
            $sourceSafe->decrement('balance', $newAmount);
            $targetSafe->increment('balance', $newAmount);

            $this->repository->update($sourceTransaction->id, [
                'total_amount'       => $newAmount,
                'process_date'       => $data['process_date'],
                'description'        => $data['description'] ?? null,
                'reference_user_id'  => $data['reference_user_id'] ?? null,
            ]);

            $this->repository->update($targetTransaction->id, [
                'total_amount'       => $newAmount,
                'process_date'       => $data['process_date'],
                'description'        => $data['description'] ?? null,
                'reference_user_id'  => $data['reference_user_id'] ?? null,
            ]);

            // Items güncelle
            $companyId = (int) session('active_company_id');
            $sourceTransaction->items()->delete();
            $targetTransaction->items()->delete();
            $this->itemRepository->createMany($sourceTransaction->id, $companyId, [
                ['transaction_category_id' => 1, 'amount' => $newAmount],
            ]);
            $this->itemRepository->createMany($targetTransaction->id, $companyId, [
                ['transaction_category_id' => 1, 'amount' => $newAmount],
            ]);

            return [
                'source' => $sourceTransaction->fresh(),
                'target' => $targetTransaction->fresh(),
            ];
        });
    }

    /**
     * Döviz çiftini güncelle — atomik.
     * sourceTransaction expense kaydıdır.
     */
    public function updateExchange(SafeTransaction $sourceTransaction, array $data): array
    {
        return DB::transaction(function () use ($sourceTransaction, $data): array {
            /** @var SafeTransaction */
            $targetTransaction = SafeTransaction::withoutGlobalScopes()
                ->findOrFail($sourceTransaction->target_transaction_id);

            $sourceSafe = $this->safeRepository->findWithLock($sourceTransaction->safe_id);
            $targetSafe = $this->safeRepository->findWithLock($targetTransaction->safe_id);

            $oldSourceAmount = (float) $sourceTransaction->total_amount;
            $oldTargetAmount = (float) $targetTransaction->total_amount;
            $newSourceAmount = (float) $data['source_amount'];
            $newTargetAmount = (float) $data['target_amount'];
            $newRate         = (float) $data['item_rate'];

            // Eski bakiyeleri geri al
            $sourceSafe->increment('balance', $oldSourceAmount);
            $targetSafe->decrement('balance', $oldTargetAmount);

            // Yeni tutarları uygula
            $this->safeService->checkBalance($sourceSafe->fresh(), $newSourceAmount);
            $sourceSafe->decrement('balance', $newSourceAmount);
            $targetSafe->increment('balance', $newTargetAmount);

            $this->repository->update($sourceTransaction->id, [
                'total_amount'       => $newSourceAmount,
                'item_rate'          => $newRate,
                'process_date'       => $data['process_date'],
                'description'        => $data['description'] ?? null,
                'reference_user_id'  => $data['reference_user_id'] ?? null,
            ]);

            $this->repository->update($targetTransaction->id, [
                'total_amount'       => $newTargetAmount,
                'item_rate'          => $newRate,
                'process_date'       => $data['process_date'],
                'description'        => $data['description'] ?? null,
                'reference_user_id'  => $data['reference_user_id'] ?? null,
            ]);

            // Items güncelle
            $companyId = (int) session('active_company_id');
            $sourceTransaction->items()->delete();
            $targetTransaction->items()->delete();
            $this->itemRepository->createMany($sourceTransaction->id, $companyId, [
                ['transaction_category_id' => 2, 'amount' => $newSourceAmount],
            ]);
            $this->itemRepository->createMany($targetTransaction->id, $companyId, [
                ['transaction_category_id' => 2, 'amount' => $newTargetAmount],
            ]);

            return [
                'source' => $sourceTransaction->fresh(),
                'target' => $targetTransaction->fresh(),
            ];
        });
    }

    /**
     * "ATAMA BEKLİYOR" (category_id=3) kaydını transfer/döviz olarak ata.
     *
     * Senaryo 4'e bölünür — operation_choice + target_safe.is_api_integration kombinasyonu:
     * - Transfer + API kasa: linking (tutar AYNI zorunlu)
     * - Transfer + Normal: INCOME/EXPENSE oluştur (tersi yön)
     * - Döviz + API kasa: liste (tutar farklı olabilir)
     * - Döviz + Normal: manuel kur+tutar
     *
     * @param SafeTransaction $source Kaynak işlem (INCOME veya EXPENSE olabilir)
     * @param array{
     *   operation_choice: string,
     *   target_safe_id: int,
     *   target_transaction_id?: int|null,
     *   exchange_rate?: float|null,
     *   target_amount?: float|null,
     * } $data
     */
    public function assignTransaction(SafeTransaction $source, array $data): void
    {
        DB::transaction(function () use ($source, $data): void {
            $companyId     = (int) session('active_company_id');
            $operationType = $data['operation_choice'];
            $targetSafeId  = (int) $data['target_safe_id'];
            $targetSafe    = Safe::with('safeGroup')->findOrFail($targetSafeId);
            $isTargetApi   = $targetSafe->safeGroup->is_api_integration;
            $categoryId    = $operationType === 'transfer' ? 1 : 2;
            $opEnum        = $operationType === 'transfer'
                ? OperationType::TRANSFER
                : OperationType::EXCHANGE;

            // ========== SENARYO A: Hedef API kasası → mevcut kaydı link et ==========
            if ($isTargetApi) {
                $targetTransactionId = $data['target_transaction_id'] ?? null;
                if ($targetTransactionId === null) {
                    throw new \Exception('Hedef kasa API entegrasyonlu, target_transaction_id gereklidir.');
                }

                /** @var SafeTransaction|null */
                $target = SafeTransaction::withoutGlobalScopes()
                    ->findOrFail($targetTransactionId);

                // Kaynak kaydı güncelle
                $source->update([
                    'operation_type'        => $opEnum,
                    'target_safe_id'        => $targetSafe->id,
                    'target_transaction_id' => $target->id,
                ]);

                // Hedef kaydı güncelle
                $target->update([
                    'operation_type'        => $opEnum,
                    'target_safe_id'        => $source->safe_id,
                    'target_transaction_id' => $source->id,
                ]);

                // Her ikisinin items kategorisini güncelle
                $source->items()->update(['transaction_category_id' => $categoryId]);
                $target->items()->update(['transaction_category_id' => $categoryId]);

                return;
            }

            // ========== SENARYO B: Hedef normal kasa → yeni kayıt oluştur ==========
            // Type tersini belirle (EXPENSE → INCOME, INCOME → EXPENSE)
            $targetType = $source->type === TransactionType::EXPENSE
                ? TransactionType::INCOME
                : TransactionType::EXPENSE;

            $sourceAmount = (float) $source->total_amount;
            $targetAmount = $sourceAmount;  // Transfer: aynı
            $exchangeRate = null;
            $itemRate     = null;

            // Döviz işlemi ise exchange_rate/target_amount zorunlu
            if ($operationType === 'exchange') {
                if (!isset($data['exchange_rate'], $data['target_amount'])) {
                    throw new \Exception('Döviz işleminde exchange_rate ve target_amount gereklidir.');
                }

                $exchangeRate = (float) $data['exchange_rate'];
                $targetAmount = (float) $data['target_amount'];  // Form'dan manual giriş
                $itemRate     = $exchangeRate;
            }

            // Hedef safe bakiye lock + güncelleme
            $targetSafeLocked = $this->safeRepository->findWithLock($targetSafeId);

            if ($targetType === TransactionType::INCOME) {
                $targetSafeLocked->increment('balance', $targetAmount);
            } else {
                // EXPENSE — bakiye kontrolü
                $this->safeService->checkBalance($targetSafeLocked, $targetAmount);
                $targetSafeLocked->decrement('balance', $targetAmount);
            }

            // Yeni hedef transaction oluştur
            /** @var SafeTransaction */
            $target = $this->repository->create([
                'company_id'            => $companyId,
                'safe_id'               => $targetSafe->id,
                'type'                  => $targetType,
                'operation_type'        => $opEnum,
                'total_amount'          => $targetAmount,
                'currency_id'           => $operationType === 'exchange' ? $targetSafe->currency_id : $source->currency_id,
                'exchange_rate'         => $exchangeRate,
                'item_rate'             => $itemRate,
                'target_safe_id'        => $source->safe_id,
                'target_transaction_id' => $source->id,
                'process_date'          => $source->process_date,
                'transaction_date'      => $source->transaction_date,
                'description'           => $source->description,
                'reference_user_id'     => $source->reference_user_id,
                'created_user_id'       => auth()->id(),
                'balance_after_created' => $targetSafeLocked->fresh()->balance,
            ]);

            // Kaynak kaydı güncelle (linking + exchange fields)
            $source->update([
                'operation_type'        => $opEnum,
                'target_safe_id'        => $targetSafe->id,
                'target_transaction_id' => $target->id,
                'exchange_rate'         => $exchangeRate,
                'item_rate'             => $itemRate,
            ]);

            // Kaynak items kategorisini güncelle
            $source->items()->update(['transaction_category_id' => $categoryId]);

            // Hedef items oluştur
            $this->itemRepository->createMany($target->id, $companyId, [
                ['transaction_category_id' => $categoryId, 'amount' => $targetAmount],
            ]);
        });
    }

    public function delete(SafeTransaction $transaction): bool
    {
        return $this->repository->delete($transaction->id);
    }

    /**
     * Ortak persist mantığı. DB::transaction içinde çalışır.
     * 1. EXPENSE ise checkBalance
     * 2. SUM(items) = total_amount kontrolü
     * 3. Transaction kaydet
     * 4. updateBalance
     * 5. balance_after_created snapshot
     * 6. Items kaydet
     */
    private function persist(array $data): SafeTransaction
    {
        return DB::transaction(function () use ($data): SafeTransaction {
            $companyId = (int) session('active_company_id');
            $safe      = Safe::findOrFail($data['safe_id']);

            $totalAmount = (float) $data['total_amount'];
            $items       = $data['items'] ?? [];

            // Split doğrulama
            if (!empty($items)) {
                $itemsSum = collect($items)->sum(fn ($i) => (float) $i['amount']);
                if (abs($itemsSum - $totalAmount) > 0.0001) {
                    throw new SplitAmountMismatchException(
                        "Kalem toplamı ({$itemsSum}) işlem tutarına ({$totalAmount}) eşit olmalıdır."
                    );
                }
            }

            // Gider kontrolü
            if ($data['type'] === TransactionType::EXPENSE->value) {
                $this->safeService->checkBalance($safe, $totalAmount);
            }

            $transactionData = array_merge($data, [
                'company_id'            => $companyId,
                'currency_id'           => $data['currency_id'] ?? $safe->currency_id,
                'created_user_id'       => auth()->id(),
                'balance_after_created' => 0,
            ]);
            unset($transactionData['items']);

            /** @var SafeTransaction */
            $transaction = $this->repository->create($transactionData);

            // Bakiye güncelle
            $this->safeService->updateBalance($safe, $data['type'], $totalAmount);

            // Snapshot
            $transaction->update(['balance_after_created' => $safe->fresh()->balance]);

            // Items kaydet
            if (!empty($items)) {
                $this->itemRepository->createMany($transaction->id, $companyId, $items);
            }

            return $transaction->load('items');
        });
    }
}
