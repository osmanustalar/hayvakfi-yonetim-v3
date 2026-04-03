<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LivestockType;
use App\Enums\TransactionType;
use App\Models\KurbanEntry;
use App\Repositories\KurbanEntryRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class KurbanEntryService
{
    public function __construct(
        private readonly KurbanEntryRepository $repository,
        private readonly SafeTransactionService $transactionService,
        private readonly ContactService $contactService,
        private readonly KurbanGroupService $groupService,
    ) {}

    public function create(array $data): KurbanEntry
    {
        return DB::transaction(function () use ($data): KurbanEntry {
            $data['company_id'] = (int) session('active_company_id');
            $data['created_user_id'] = auth()->id();

            // Telefon varsa ve contact_id yoksa, otomatik contact bul
            if (! empty($data['phone']) && empty($data['contact_id'])) {
                $contact = $this->contactService->findByPhone($data['phone']);
                if ($contact !== null) {
                    $data['contact_id'] = $contact->id;
                }
            }

            // Sıra numarası hesaplama (Pessimistic Lock ile yarışma durumunu engeller)
            $list = \App\Models\KurbanList::findOrFail($data['kurban_list_id']);
            $seasonId = $list->kurban_season_id;
            
            \App\Models\KurbanSeason::where('id', $seasonId)->lockForUpdate()->first();

            $usedNumbers = \App\Models\KurbanEntry::whereHas('list', function ($q) use ($seasonId) {
                    $q->where('kurban_season_id', $seasonId);
                })
                ->whereNotNull('queue_number')
                ->orderBy('queue_number')
                ->lockForUpdate()
                ->pluck('queue_number')
                ->toArray();

            $nextNumber = 1;
            foreach ($usedNumbers as $num) {
                if ($num == $nextNumber) {
                    $nextNumber++;
                } elseif ($num > $nextNumber) {
                    break;
                }
            }
            $data['queue_number'] = $nextNumber;

            /** @var KurbanEntry */
            $entry = $this->repository->create($data);

            // Büyük baş ise aynı liste içinde gruba ata
            if (($data['livestock_type'] ?? null) === LivestockType::LARGE->value
                || ($data['livestock_type'] ?? null) === LivestockType::LARGE) {
                $this->groupService->assignToGroup($entry);
            }

            return $entry;
        });
    }

    public function update(KurbanEntry $entry, array $data): KurbanEntry
    {
        $this->repository->update($entry->id, $data);

        return $entry->refresh();
    }

    public function delete(KurbanEntry $entry): bool
    {
        return $this->repository->delete($entry->id);
    }

    /**
     * Birden fazla kurban kaydını toplu olarak ödendi işaretle.
     *
     * @param  array<int>  $entryIds
     * @param array{
     *   safe_id: int,
     *   process_date: string,
     *   reference_user_id: int|null,
     *   description: string|null,
     * } $paymentData
     * @return Collection<int, KurbanEntry>
     */
    public function bulkMarkAsPaid(array $entryIds, array $paymentData): Collection
    {
        return DB::transaction(function () use ($entryIds, $paymentData): Collection {
            $entries = KurbanEntry::whereIn('id', $entryIds)
                ->where('is_paid', false)
                ->get();

            $updatedEntries = collect();

            foreach ($entries as $entry) {
                // 1. SafeTransaction oluştur (INCOME, kategori: kurban tipi)
                $transaction = $this->transactionService->create([
                    'safe_id' => $paymentData['safe_id'],
                    'type' => TransactionType::INCOME->value,
                    'total_amount' => $entry->safeTransaction?->total_amount ?? 0,
                    'process_date' => $paymentData['process_date'],
                    'description' => $paymentData['description']
                        ?? "Kurban bağışı - {$entry->full_name} - {$entry->sacrificeCategory?->name}",
                    'contact_id' => $entry->contact_id,
                    'reference_user_id' => $paymentData['reference_user_id'] ?? null,
                    'items' => [
                        [
                            'transaction_category_id' => $entry->sacrifice_category_id,
                            'amount' => $entry->safeTransaction?->total_amount ?? 0,
                        ],
                    ],
                ]);

                // 2. Contact oluştur/güncelle (telefon varsa ve contact yoksa)
                if ($entry->contact_id === null && ! empty($entry->phone)) {
                    $contact = $this->contactService->findByPhone($entry->phone);
                    if ($contact === null) {
                        $contact = $this->contactService->create([
                            'first_name' => $entry->first_name,
                            'last_name' => $entry->last_name,
                            'phone' => $entry->phone,
                            'is_donor' => true,
                        ]);
                    } else {
                        // Mevcut kişiyi bağışçı olarak işaretle
                        if (! $contact->is_donor) {
                            $contact->update(['is_donor' => true]);
                        }
                    }
                    $entry->update(['contact_id' => $contact->id]);
                    $transaction->update(['contact_id' => $contact->id]);
                }

                // 3. Kurban kaydını ödendi olarak işaretle
                $entry->update([
                    'is_paid' => true,
                    'paid_date' => $paymentData['process_date'],
                    'safe_transaction_id' => $transaction->id,
                ]);

                $updatedEntries->push($entry->refresh());
            }

            return $updatedEntries;
        });
    }
}
