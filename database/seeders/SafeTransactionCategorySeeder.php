<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SafeTransactionCategory;
use Illuminate\Database\Seeder;

class SafeTransactionCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Sabit ID'ler — sıra kesinlikle bozulmaz.
        // ID 1 — Hesaplar Arası Para Transferleri
        SafeTransactionCategory::updateOrCreate(
            ['id' => 1],
            [
                'company_id'           => null,
                'name'                 => 'Hesaplar Arası Para Transferleri',
                'type'                 => null,
                'parent_id'            => null,
                'sort_order'           => 0,
                'is_active'            => true,
                'is_disable_in_report' => true,
                'contact_type'         => null,
                'color'                => null,
                'description'          => null,
                'created_user_id'      => null,
            ]
        );

        // ID 2 — Döviz İşlemleri
        SafeTransactionCategory::updateOrCreate(
            ['id' => 2],
            [
                'company_id'           => null,
                'name'                 => 'Döviz İşlemleri',
                'type'                 => null,
                'parent_id'            => null,
                'sort_order'           => 0,
                'is_active'            => true,
                'is_disable_in_report' => true,
                'contact_type'         => null,
                'color'                => null,
                'description'          => null,
                'created_user_id'      => null,
            ]
        );

        // ID 3 — ATAMA BEKLİYOR
        SafeTransactionCategory::updateOrCreate(
            ['id' => 3],
            [
                'company_id'           => null,
                'name'                 => 'ATAMA BEKLİYOR',
                'type'                 => null,
                'parent_id'            => null,
                'sort_order'           => 0,
                'is_active'            => true,
                'is_disable_in_report' => false,
                'contact_type'         => null,
                'color'                => null,
                'description'          => null,
                'created_user_id'      => null,
            ]
        );

        // ID 4 — Açılış (is_active: false)
        SafeTransactionCategory::updateOrCreate(
            ['id' => 4],
            [
                'company_id'           => null,
                'name'                 => 'Açılış',
                'type'                 => 'income',
                'parent_id'            => null,
                'sort_order'           => 0,
                'is_active'            => false,
                'is_disable_in_report' => false,
                'contact_type'         => null,
                'color'                => null,
                'description'          => null,
                'created_user_id'      => null,
            ]
        );

        // ID 5 — Bağış - Kurban (üst kategori)
        SafeTransactionCategory::updateOrCreate(
            ['id' => 5],
            [
                'company_id'           => null,
                'name'                 => 'Bağış - Kurban',
                'type'                 => 'income',
                'parent_id'            => null,
                'sort_order'           => 10,
                'is_active'            => true,
                'is_disable_in_report' => false,
                'contact_type'         => 'donor',
                'color'                => null,
                'description'          => null,
                'created_user_id'      => null,
            ]
        );

        // Alt kategoriler (parent_id: 5)
        $subcategories = [
            6  => 'Genel Bağış',
            7  => 'Zekat Bağışı',
            8  => 'Akika Kurbanı',
            9  => 'Vacip Kurbanı',
            10 => 'Adak Kurbanı',
            11 => 'Sadaka Kurbanı',
            12 => 'Fitre',
            13 => 'Kumanya',
            14 => 'Öğrenci İftarı',
            15 => 'Hatim',
        ];

        $sortOrder = 0;
        foreach ($subcategories as $id => $name) {
            $sortOrder += 10;
            SafeTransactionCategory::updateOrCreate(
                ['id' => $id],
                [
                    'company_id'           => null,
                    'name'                 => $name,
                    'type'                 => 'income',
                    'parent_id'            => 5,
                    'sort_order'           => $sortOrder,
                    'is_active'            => true,
                    'is_disable_in_report' => false,
                    'contact_type'         => 'donor',
                    'color'                => null,
                    'description'          => null,
                    'created_user_id'      => null,
                ]
            );
        }

        // Ek kategoriler (v2'den gelen)
        $additionalCategories = [
            ['name' => 'Öğrenci Aidat', 'type' => 'income', 'sort_order' => 200],
            ['name' => 'Öğrenci Gideri', 'type' => 'expense', 'sort_order' => 210],
            ['name' => 'Ev Kira Geliri', 'type' => 'income', 'sort_order' => 220],
            ['name' => 'Ev Gideri', 'type' => 'expense', 'sort_order' => 230],
            ['name' => 'Toy Salonu Geliri', 'type' => 'income', 'sort_order' => 240],
            ['name' => 'Toy Salonu Gideri', 'type' => 'expense', 'sort_order' => 250],
            ['name' => 'Market Alışverişi', 'type' => 'expense', 'sort_order' => 260],
            ['name' => 'Burs', 'type' => 'expense', 'sort_order' => 270],
            ['name' => 'Diğer Gelir', 'type' => 'income', 'sort_order' => 280],
            ['name' => 'Diğer Gider', 'type' => 'expense', 'sort_order' => 290],
            ['name' => 'Bina Bakım', 'type' => 'expense', 'sort_order' => 300],
            ['name' => 'Araba Bakım', 'type' => 'expense', 'sort_order' => 310],
            ['name' => 'Maaş', 'type' => 'expense', 'sort_order' => 320],
            ['name' => 'Asansör', 'type' => 'expense', 'sort_order' => 330],
            ['name' => 'Araba Yakıt', 'type' => 'expense', 'sort_order' => 340],
            ['name' => 'HGS', 'type' => 'expense', 'sort_order' => 350],
            ['name' => 'Telefon', 'type' => 'expense', 'sort_order' => 360],
            ['name' => 'Elektrik', 'type' => 'expense', 'sort_order' => 370],
            ['name' => 'Doğalgaz', 'type' => 'expense', 'sort_order' => 380],
            ['name' => 'Su', 'type' => 'expense', 'sort_order' => 390],
            ['name' => 'Etüt Merkezi Geliri', 'type' => 'income', 'sort_order' => 400],
            ['name' => 'Etüt Merkezi Gideri', 'type' => 'expense', 'sort_order' => 410],
        ];

        foreach ($additionalCategories as $category) {
            SafeTransactionCategory::updateOrCreate(
                ['name' => $category['name']],
                [
                    'company_id'           => null,
                    'name'                 => $category['name'],
                    'type'                 => $category['type'],
                    'parent_id'            => null,
                    'sort_order'           => $category['sort_order'],
                    'is_active'            => true,
                    'is_disable_in_report' => false,
                    'contact_type'         => null,
                    'color'                => null,
                    'description'          => null,
                    'created_user_id'      => null,
                ]
            );
        }
    }
}
