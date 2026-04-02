<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

use App\Models\SafeTransactionCategory;

class CategoryMigrator extends BaseMigrator
{
    public function count(): int
    {
        return $this->v1()->table('transaction_categories')->count();
    }

    public function migrate(bool $fresh = false): void
    {
        // V3'te kategoriler SafeTransactionCategorySeeder tarafından yönetiliyor
        // Burada sadece eksik kategorileri ekle

        $companyId = null; // global kategoriler

        // Sabit ID'ler (1-5) V3'te zaten var, değişme yok

        // V1 kategorileri oku
        $v1Categories = $this->v1()->table('transaction_categories')->get();

        // ID mapping oluştur (V1 ID -> V3 mapping)
        $idMap = [
            1 => 1,  // Hesaplar Arası Para Transferleri
            2 => 2,  // Döviz İşlemleri
            3 => 3,  // ATAMA BEKLİYOR
            4 => 4,  // Açılış
            5 => 5,  // Bağış-Kurban (üst kategori)
            6 => 11, // Hatim (alt kategori parent_id=5)
        ];

        // V1 6'dan sonraki kategorileri (7-26) güncelle/ekle
        $nextV3Id = 16; // V3'te ID 1-15 zaten tanımlı

        foreach ($v1Categories as $v1Cat) {
            if ($v1Cat->id <= 5) {
                // Sabit kategoriler, değişme yok
                continue;
            }

            if ($v1Cat->id === 6) {
                // Hatim zaten harita da
                continue;
            }

            // V1 "Diğer" kategorileri için özel handling
            if ($v1Cat->name === 'Diğer') {
                $mappedName = $v1Cat->type === 'income' ? 'Diğer Gelir' : 'Diğer Gider';
                $v3Cat = SafeTransactionCategory::where('name', $mappedName)->first();
                if ($v3Cat) {
                    $idMap[$v1Cat->id] = $v3Cat->id;

                    continue; // Yeni kayıt oluşturma
                }
            }

            // V1 kategorisini V3'te aynı isimle bul veya güncelle
            $v3Cat = SafeTransactionCategory::where('name', $v1Cat->name)->first();

            if (! $v3Cat) {
                // Yeni kategori ekle
                $v3Cat = SafeTransactionCategory::create([
                    'name' => $v1Cat->name,
                    'type' => $v1Cat->type,
                    'parent_id' => null,
                    'company_id' => $companyId,
                    'sort_order' => ($v1Cat->id * 10),
                    'is_active' => (bool) $v1Cat->is_active,
                    'is_disable_in_report' => (bool) $v1Cat->is_disable_in_report,
                    'contact_type' => null,
                    'color' => $v1Cat->color ?? '#CCCCCC',
                    'created_user_id' => $v1Cat->created_user_id,
                    'created_at' => $v1Cat->created_at,
                    'updated_at' => $v1Cat->updated_at,
                ]);
            } else {
                // Mevcut kategoriyi güncelle
                $v3Cat->update([
                    'type' => $v1Cat->type,
                    'is_active' => (bool) $v1Cat->is_active,
                    'is_disable_in_report' => (bool) $v1Cat->is_disable_in_report,
                    'color' => $v1Cat->color ?? '#CCCCCC',
                ]);
            }

            // Bu V1 ID'nin V3 haritası için ID mapping ekle
            if ($v3Cat) {
                $idMap[$v1Cat->id] = $v3Cat->id;
            }
        }

        // Kurban alt kategorisi zaten SafeTransactionCategorySeeder tarafından oluşturuluyor (ID 16)

        // ID mapping'i cache'de sakla (TransactionMigrator'da kullanılacak)
        \Cache::put('v1_category_map', $idMap, now()->addHours(24));
    }

    /**
     * V1 donation_category_id'den V3 transaction_category_id'ye eşleştirme
     */
    public function getDonationCategoryMap(): array
    {
        return [
            1 => 6,  // Bağış -> Genel Bağış
            2 => 7,  // Fitre/Zekat -> Zekat Bağışı
            3 => 16, // Kurban -> Kurban alt kategorisi (ID 16)
        ];
    }

    /**
     * V1 transaction_category_id -> V3 safe_transaction_category_id mapping
     */
    public function getCategoryIdMap(): array
    {
        return \Cache::get('v1_category_map', [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 11,
        ]);
    }
}
