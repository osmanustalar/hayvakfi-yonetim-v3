<?php

declare(strict_types=1);

namespace App\Services\V1Migration\Traits;

use App\Enums\TransactionType;

trait ResolvesCategories
{
    /**
     * V1 kategori ID'sini V3'te eşleştir.
     * Bağış ve Kurban alt kategorileri için isim tabanlı eşleştirme yapılır.
     * "Diğer" kategorileri type'a göre eşleştirilir.
     */
    protected function resolveCategoryId(?int $v1CategoryId, TransactionType $type): ?int
    {
        if ($v1CategoryId === null) {
            return null;
        }

        $v1Category = $this->v1()->table('transaction_categories')->where('id', $v1CategoryId)->first();

        if (! $v1Category) {
            return null;
        }

        $categoryName = trim($v1Category->name);

        // ===== BAĞIŞ ALT KATEGORİLERİ =====
        // Zekat Bağışı
        if (stripos($categoryName, 'Zekat') !== false && stripos($categoryName, 'Bağış') !== false) {
            return 7; // Zekat (parent: 5 - Bağış)
        }

        // Fitre
        if (stripos($categoryName, 'Fitre') !== false) {
            return 8; // Fitre (parent: 5 - Bağış)
        }

        // Kumanya
        if (stripos($categoryName, 'Kumanya') !== false) {
            return 9; // Kumanya (parent: 5 - Bağış)
        }

        // Öğrenci İftarı
        if (stripos($categoryName, 'Öğrenci') !== false && stripos($categoryName, 'İftarı') !== false) {
            return 10; // Öğrenci İftarı (parent: 5 - Bağış)
        }

        // Hatim
        if (stripos($categoryName, 'Hatim') !== false) {
            return 11; // Hatim (parent: 5 - Bağış)
        }

        // Genel Bağış veya sadece "Bağış"
        if ($categoryName === 'Bağış' || $categoryName === 'Genel Bağış') {
            return 6; // Genel (parent: 5 - Bağış)
        }

        // ===== KURBAN ALT KATEGORİLERİ =====
        // Vacip Kurban
        if (stripos($categoryName, 'Vacip') !== false && stripos($categoryName, 'Kurban') !== false) {
            return 13; // Vacip Kurban (parent: 12 - Kurban)
        }

        // Akika Kurbanı
        if (stripos($categoryName, 'Akika') !== false) {
            return 14; // Akika Kurbanı (parent: 12 - Kurban)
        }

        // Sadaka Kurbanı
        if (stripos($categoryName, 'Sadaka') !== false && stripos($categoryName, 'Kurban') !== false) {
            return 15; // Sadaka Kurbanı (parent: 12 - Kurban)
        }

        // Adak Kurbanı
        if (stripos($categoryName, 'Adak') !== false && stripos($categoryName, 'Kurban') !== false) {
            return 16; // Adak Kurbanı (parent: 12 - Kurban)
        }

        // Diğer Kurban kategorileri
        if ($categoryName === 'Kurban' || stripos($categoryName, 'Kurban') !== false) {
            return 12; // Kurban parent kategorisi
        }

        // ===== DİĞER KATEGORİLER =====
        // "Diğer" kategorisini type'a göre eşleştir
        if ($categoryName === 'Diğer') {
            $mappedCategoryName = $type === TransactionType::INCOME ? 'Diğer Gelir' : 'Diğer Gider';
            $v3Category = $this->v3()->table('safe_transaction_categories')->where('name', $mappedCategoryName)->first();

            return $v3Category?->id;
        }

        // Sabit ID eşleştirmelerinde kontrol et
        if (isset($this->categoryMap[$v1CategoryId])) {
            return $this->categoryMap[$v1CategoryId];
        }

        return null;
    }
}
