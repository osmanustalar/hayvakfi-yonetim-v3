<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

use App\Enums\TransactionType;
use App\Enums\OperationType;
use App\Models\SafeTransaction;

class TransactionMigrator extends BaseMigrator
{
    private array $currencyMap = [];
    private array $categoryMap = [];
    private array $donationCategoryMap = [];

    public function __construct(
        private CurrencyMigrator $currencyMigrator,
        private CategoryMigrator $categoryMigrator,
    ) {}

    public function count(): int
    {
        return $this->v1()->table('transactions')->count();
    }

    public function migrate(bool $fresh = false): void
    {
        $this->currencyMap = $this->currencyMigrator->getIdMap();
        $this->categoryMap = $this->categoryMigrator->getCategoryIdMap();
        $this->donationCategoryMap = $this->categoryMigrator->getDonationCategoryMap();

        if ($fresh) {
            $this->truncate('safe_transactions');
        }

        $companyId = $this->v3()->table('companies')->first()?->id ?? 1;
        $v1Transactions = $this->v1()->table('transactions')->orderBy('id')->get();

        foreach ($v1Transactions as $v1Tx) {
            $currencyId = $v1Tx->currency_id ? ($this->currencyMap[$v1Tx->currency_id] ?? $v1Tx->currency_id) : null;

            // Tipi string'den enum'a çevir
            $type = $v1Tx->type === 'income' ? TransactionType::INCOME : TransactionType::EXPENSE;

            // Kategori eşleştirmesi (V1 "Diğer" kategorisi özel handling)
            $categoryId = $this->resolveCategoryId($v1Tx->transaction_category_id, $type);

            // operation_type türet
            $operationType = null;
            if ($categoryId === 1) {
                $operationType = OperationType::TRANSFER->value;
            } elseif ($categoryId === 2) {
                $operationType = OperationType::EXCHANGE->value;
            }

            // UNIQUE constraint: safe_id + integration_id kontrolü
            // Duplicate integration_id'li işlem varsa skip et
            if ($v1Tx->bank_payment_id) {
                $duplicate = $this->v3()
                    ->table('safe_transactions')
                    ->where('safe_id', $v1Tx->safe_id)
                    ->where('integration_id', $v1Tx->bank_payment_id)
                    ->first();
                if ($duplicate) {
                    continue; // Duplicate, skip et
                }
            }

            // Direct insert ID'yi tutarak
            $existing = $this->v3()->table('safe_transactions')->where('id', $v1Tx->id)->first();
            if ($existing) {
                $this->v3()->table('safe_transactions')->where('id', $v1Tx->id)->update([
                    'company_id' => $companyId,
                    'safe_id' => $v1Tx->safe_id,
                    'type' => $type->value,
                    'operation_type' => $operationType,
                    'total_amount' => $v1Tx->amount,
                    'currency_id' => $currencyId,
                    'contact_id' => $v1Tx->donor_id,
                    'reference_user_id' => $v1Tx->reference_user_id,
                    'created_user_id' => $v1Tx->created_user_id,
                    'process_date' => $v1Tx->process_date,
                    'integration_id' => $v1Tx->bank_payment_id,
                    'balance_after_created' => $v1Tx->balance_after_created ?? 0,
                    'import_file' => $v1Tx->import_file,
                    'target_safe_id' => $v1Tx->target_safe_id,
                    'target_transaction_id' => $v1Tx->target_transaction_id,
                    'is_show' => true,
                    'description' => $v1Tx->description,
                    'updated_at' => $v1Tx->updated_at,
                ]);
            } else {
                // target_transaction_id kontrol: eğer hedef transaction'a henüz INSERT edilmemişse NULL bırak (sonra bir pass'de düzelt)
                $targetTxId = null;
                if ($v1Tx->target_transaction_id && $this->v3()->table('safe_transactions')->where('id', $v1Tx->target_transaction_id)->exists()) {
                    $targetTxId = $v1Tx->target_transaction_id;
                }

                $this->v3()->table('safe_transactions')->insert([
                    'id' => $v1Tx->id,
                    'company_id' => $companyId,
                    'safe_id' => $v1Tx->safe_id,
                    'type' => $type->value,
                    'operation_type' => $operationType,
                    'total_amount' => $v1Tx->amount,
                    'currency_id' => $currencyId,
                    'contact_id' => $v1Tx->donor_id,
                    'reference_user_id' => $v1Tx->reference_user_id,
                    'created_user_id' => $v1Tx->created_user_id,
                    'process_date' => $v1Tx->process_date,
                    'integration_id' => $v1Tx->bank_payment_id,
                    'balance_after_created' => $v1Tx->balance_after_created ?? 0,
                    'import_file' => $v1Tx->import_file,
                    'target_safe_id' => $v1Tx->target_safe_id,
                    'target_transaction_id' => $targetTxId,
                    'is_show' => true,
                    'description' => $v1Tx->description,
                    'created_at' => $v1Tx->created_at,
                    'updated_at' => $v1Tx->updated_at,
                    'deleted_at' => $v1Tx->deleted_at,
                ]);
            }
        }

        // İkinci pass: target_transaction_id'leri güncelleĢek
        $this->updateTargetTransactionIds();
    }

    private function updateTargetTransactionIds(): void
    {
        $v1Transactions = $this->v1()->table('transactions')->where('target_transaction_id', '!=', null)->get();

        foreach ($v1Transactions as $v1Tx) {
            if ($v1Tx->target_transaction_id && $this->v3()->table('safe_transactions')->where('id', $v1Tx->target_transaction_id)->exists()) {
                $this->v3()->table('safe_transactions')
                    ->where('id', $v1Tx->id)
                    ->update(['target_transaction_id' => $v1Tx->target_transaction_id]);
            }
        }
    }

    /**
     * V1 transaction'dan V3 safe_transaction'a eşleştirme
     */
    public function getTransactionIdMap(): array
    {
        $map = [];
        $v1Txs = $this->v1()->table('transactions')->pluck('id');
        foreach ($v1Txs as $v1Id) {
            $v3Tx = $this->v3()->table('safe_transactions')->where('id', $v1Id)->first();
            if ($v3Tx) {
                $map[$v1Id] = $v3Tx->id;
            }
        }
        return $map;
    }

    /**
     * V1 kategori ID'sini V3'te eşleştir.
     * Kurban alt kategorileri için isim tabanlı eşleştirme yapılır.
     * "Bağış" kategorileri "Bağış > Genel"e, "Diğer" kategorileri type'a göre eşleştirilir.
     */
    private function resolveCategoryId(?int $v1CategoryId, TransactionType $type): ?int
    {
        if ($v1CategoryId === null) {
            return null;
        }

        // V1'deki kategorinin adını kontrol et
        $v1Category = $this->v1()->table('transaction_categories')->where('id', $v1CategoryId)->first();

        if (!$v1Category) {
            return null;
        }

        // Kurban alt kategorileri için isim tabanlı eşleştirme
        $categoryName = trim($v1Category->name);

        // Specific Kurban subcategory mappings
        if (stripos($categoryName, 'Vacip') !== false && stripos($categoryName, 'Kurban') !== false) {
            return 13; // Vacip Kurban
        }

        if (stripos($categoryName, 'Akika') !== false) {
            return 14; // Akika Kurbanı
        }

        if (stripos($categoryName, 'Sadaka') !== false && stripos($categoryName, 'Kurban') !== false) {
            return 15; // Sadaka Kurbanı
        }

        if (stripos($categoryName, 'Adak') !== false && stripos($categoryName, 'Kurban') !== false) {
            return 16; // Adak Kurbanı
        }

        // Diğer Kurban kategorileri (eğer parent kategori sadece "Kurban" ise)
        if ($categoryName === 'Kurban' || stripos($categoryName, 'Kurban') !== false) {
            return 12; // Kurban parent kategorisi
        }

        // "Bağış / Yardım / Zekat / Kurban" ortak kategorisini "Bağış > Genel" (ID 6) olarak eşleştir
        if (strpos($categoryName, 'Bağış') !== false && strpos($categoryName, 'Zekat') !== false) {
            return 6; // Bağış > Genel kategorisi
        }

        // Sadece "Bağış" diye başlayan kategorileri "Bağış > Genel"e eşleştir
        if ($categoryName === 'Bağış' || stripos($categoryName, 'Bağış') === 0) {
            return 6; // Bağış > Genel kategorisi
        }

        // "Diğer" kategorisini type'a göre "Diğer Gelir" veya "Diğer Gider"e eşleştir
        if ($categoryName === 'Diğer') {
            $mappedCategoryName = $type === TransactionType::INCOME ? 'Diğer Gelir' : 'Diğer Gider';
            $v3Category = $this->v3()->table('safe_transaction_categories')->where('name', $mappedCategoryName)->first();
            return $v3Category?->id;
        }

        // Diğer kategoriler için sabit ID eşleştirmelerinde kontrol et
        if (isset($this->categoryMap[$v1CategoryId])) {
            return $this->categoryMap[$v1CategoryId];
        }

        // Eşleşme bulunamazsa V1 ID'sini olduğu gibi kullan (fallback)
        return $v1CategoryId;
    }
}
