<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FeeStatus;
use App\Enums\TransactionType;
use App\Models\SafeTransactionCategory;
use App\Models\StudentFee;
use App\Repositories\StudentFeeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StudentFeeService
{
    public function __construct(
        private readonly StudentFeeRepository $repository,
        private readonly SafeTransactionService $safeTransactionService
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->all();
    }

    public function find(int $id): ?StudentFee
    {
        return $this->repository->find($id);
    }

    public function getPendingFees(): Collection
    {
        return $this->repository->getPendingFees();
    }

    public function getOverdueFees(): Collection
    {
        return $this->repository->getOverdueFees();
    }

    public function create(array $data): StudentFee
    {
        return DB::transaction(function () use ($data): StudentFee {
            $data['company_id'] = (int) session('active_company_id');
            $data['status'] = $data['status'] ?? FeeStatus::PENDING->value;

            $existing = $this->repository->findByEnrollmentAndPeriod(
                (int) $data['enrollment_id'],
                $data['period']
            );
            if ($existing !== null) {
                throw new \Exception('Bu dönem için zaten bir aidat kaydı mevcut.');
            }

            return $this->repository->create($data);
        });
    }

    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data): bool {
            return $this->repository->update($id, $data);
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            return $this->repository->delete($id);
        });
    }

    /**
     * Aidatı öde — atomik işlem.
     * 1. SafeTransactionService::create() → INCOME, kategori: Öğrenci Aidat
     * 2. student_fees.payment_transaction_id = transaction.id
     * 3. student_fees.status = PAID, paid_at = now()
     */
    public function markAsPaid(int $feeId, int $safeId, ?string $processDate = null): StudentFee
    {
        return DB::transaction(function () use ($feeId, $safeId, $processDate): StudentFee {
            /** @var StudentFee */
            $fee = $this->repository->find($feeId);

            if ($fee === null) {
                throw new \Exception('Aidat kaydı bulunamadı.');
            }

            if ($fee->status === FeeStatus::PAID) {
                throw new \Exception('Bu aidat zaten ödenmiş.');
            }

            // Öğrenci Aidat kategorisini bul (sistem veya şirket kategorisi)
            $category = SafeTransactionCategory::where('name', 'Öğrenci Aidat')
                ->where(fn ($q) => $q->whereNull('company_id')
                    ->orWhere('company_id', session('active_company_id')))
                ->first();
            if ($category === null) {
                throw new \Exception('Öğrenci Aidat kategorisi bulunamadı.');
            }

            // Kasa işlemi oluştur
            $transaction = $this->safeTransactionService->create([
                'safe_id' => $safeId,
                'type' => TransactionType::INCOME->value,
                'total_amount' => (float) $fee->amount,
                'process_date' => $processDate ?? now()->format('Y-m-d'),
                'description' => 'Öğrenci Aidat - ' . $fee->enrollment->contact->first_name . ' ' . $fee->enrollment->contact->last_name,
                'contact_id' => $fee->enrollment->contact_id,
                'items' => [
                    [
                        'transaction_category_id' => $category->id,
                        'amount' => (float) $fee->amount,
                    ],
                ],
            ]);

            // Aidat kaydını güncelle
            $this->repository->update($feeId, [
                'payment_transaction_id' => $transaction->id,
                'status' => FeeStatus::PAID->value,
                'paid_at' => now(),
            ]);

            return $fee->fresh();
        });
    }
}
