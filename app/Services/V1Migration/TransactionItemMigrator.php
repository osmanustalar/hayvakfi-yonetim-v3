<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

use App\Enums\TransactionType;
use App\Models\SafeTransactionItem;
use Illuminate\Support\Facades\DB;

class TransactionItemMigrator extends BaseMigrator
{
    private array $categoryMap = [];
    private array $donationCategoryMap = [];
    private array $transactionMap = [];

    public function __construct(private CategoryMigrator $categoryMigrator) {}

    public function count(): int
    {
        return $this->v1()->table('transaction_items')->count();
    }

    public function migrate(bool $fresh = false): void
    {
        $this->categoryMap = $this->categoryMigrator->getCategoryIdMap();
        $this->donationCategoryMap = $this->categoryMigrator->getDonationCategoryMap();
        $this->transactionMap = $this->buildTransactionIdMap();

        if ($fresh) {
            $this->truncate('safe_transaction_items');
        }

        $companyId = $this->v3()->table('companies')->first()?->id ?? 1;

        // V1 duplicate transaction'ları bul (safe_id + integration_id)
        $duplicateTransactionIds = $this->findDuplicateTransactionIds();

        // V1 transaction_items varsa taşı
        $v1Items = $this->v1()->table('transaction_items')->get();

        foreach ($v1Items as $v1Item) {
            // V1'de transaction duplicate ise, bu item'ları da skip et
            if (in_array($v1Item->transaction_id, $duplicateTransactionIds)) {
                continue;
            }

            $transactionId = $this->transactionMap[$v1Item->transaction_id] ?? $v1Item->transaction_id;

            // Transaction'ın V3'te var mı kontrol et (duplicate skiplenmiş olabilir)
            if (! $this->v3()->table('safe_transactions')->where('id', $transactionId)->exists()) {
                continue; // Skip et, parent transaction yok
            }

            // Transaction tipi bul
            $v1Transaction = $this->v1()->table('transactions')->where('id', $v1Item->transaction_id)->first();
            $type = $v1Transaction && $v1Transaction->type === 'income' ? TransactionType::INCOME : TransactionType::EXPENSE;

            $categoryId = $this->resolveCategoryId($v1Item->transaction_category_id, $type);

            // donation_category_id eşleştir
            $donationCategoryId = null;
            if ($v1Item->donation_category_id && isset($this->donationCategoryMap[$v1Item->donation_category_id])) {
                $donationCategoryId = $this->donationCategoryMap[$v1Item->donation_category_id];
            }

            // Direct insert
            $existing = $this->v3()->table('safe_transaction_items')->where('id', $v1Item->id)->first();
            if ($existing) {
                $this->v3()->table('safe_transaction_items')->where('id', $v1Item->id)->update([
                    'company_id' => $companyId,
                    'transaction_id' => $transactionId,
                    'transaction_category_id' => $categoryId,
                    'donation_category_id' => $donationCategoryId,
                    'amount' => $v1Item->amount,
                    'updated_at' => $v1Item->updated_at,
                ]);
            } else {
                $this->v3()->table('safe_transaction_items')->insert([
                    'id' => $v1Item->id,
                    'company_id' => $companyId,
                    'transaction_id' => $transactionId,
                    'transaction_category_id' => $categoryId,
                    'donation_category_id' => $donationCategoryId,
                    'amount' => $v1Item->amount,
                    'created_at' => $v1Item->created_at,
                    'updated_at' => $v1Item->updated_at,
                    'deleted_at' => $v1Item->deleted_at,
                ]);
            }
        }

        // V1'de transaction_items yoksa ama transaction varsa -> item oluştur
        $this->createMissingItems($companyId, $duplicateTransactionIds);
    }

    private function createMissingItems(int $companyId, array $duplicateTransactionIds): void
    {
        // V1 transaction'lardan item'sı olmayan olanları bul
        $v1TxsWithoutItems = $this->v1()
            ->table('transactions as t')
            ->leftJoin('transaction_items as ti', 't.id', '=', 'ti.transaction_id')
            ->whereNull('ti.id')
            ->select('t.*')
            ->get();

        foreach ($v1TxsWithoutItems as $v1Tx) {
            // V1'de transaction duplicate ise, bu transaction'ları da skip et
            if (in_array($v1Tx->id, $duplicateTransactionIds)) {
                continue;
            }

            $transactionId = $this->transactionMap[$v1Tx->id] ?? $v1Tx->id;

            // Transaction'ın V3'te var mı kontrol et (duplicate skiplenmiş olabilir)
            if (! $this->v3()->table('safe_transactions')->where('id', $transactionId)->exists()) {
                continue; // Skip et, parent transaction yok
            }

            $type = $v1Tx->type === 'income' ? TransactionType::INCOME : TransactionType::EXPENSE;
            $categoryId = $this->resolveCategoryId($v1Tx->transaction_category_id, $type);

            $donationCategoryId = null;
            if ($v1Tx->donation_category_id && isset($this->donationCategoryMap[$v1Tx->donation_category_id])) {
                $donationCategoryId = $this->donationCategoryMap[$v1Tx->donation_category_id];
            }

            SafeTransactionItem::create([
                'company_id' => $companyId,
                'transaction_id' => $transactionId,
                'transaction_category_id' => $categoryId,
                'donation_category_id' => $donationCategoryId,
                'amount' => $v1Tx->amount,
                'created_at' => $v1Tx->created_at,
                'updated_at' => $v1Tx->updated_at,
            ]);
        }
    }

    private function buildTransactionIdMap(): array
    {
        $map = [];
        $v1Txs = $this->v1()->table('transactions')->pluck('id');
        foreach ($v1Txs as $v1Id) {
            $map[$v1Id] = $v1Id; // ID'ler aynı kalıyor
        }
        return $map;
    }

    private function findDuplicateTransactionIds(): array
    {
        // V1'deki tüm transaction'ları kontrol et
        $allTxs = $this->v1()->table('transactions')->orderBy('id')->get();

        $seenCombos = [];
        $skipIds = [];

        foreach ($allTxs as $tx) {
            if (! $tx->bank_payment_id) {
                continue; // bank_payment_id yoksa duplicate olma ihtimali az
            }

            $combo = $tx->safe_id . '|' . $tx->bank_payment_id;

            if (isset($seenCombos[$combo])) {
                // İkinci ve sonraki duplicate'leri skip et
                $skipIds[] = $tx->id;
            } else {
                // İlk duplicate'i tut
                $seenCombos[$combo] = true;
            }
        }

        return $skipIds;
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
