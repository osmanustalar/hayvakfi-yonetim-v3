<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Safe;
use App\Models\SafeGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class SafeSeeder extends Seeder
{
    public function run(): void
    {
        // Get references
        $company = Company::where('name', 'HAYVAKFI')->first();
        $user    = User::where('phone', '05556260886')->first();

        if (!$company) {
            throw new \RuntimeException('HAYVAKFI company not found. Run CompanySeeder first.');
        }
        if (!$user) {
            throw new \RuntimeException('User not found. Run UserSeeder first.');
        }

        $tlId = Currency::where('name', 'TL')->first()?->id;
        $usdId = Currency::where('name', 'USD')->first()?->id;
        $eurId = Currency::where('name', 'EUR')->first()?->id;

        if (!$tlId || !$usdId || !$eurId) {
            throw new \RuntimeException('Currencies not found. Run CurrencySeeder first.');
        }

        // SafeGroups
        $safeGroups = [
            [
                'name'               => 'Vakıf',
                'is_active'          => true,
                'is_api_integration' => false,
                'credentials'        => null,
            ],
            [
                'name'               => 'Kuveyt Türk',
                'is_active'          => true,
                'is_api_integration' => true,
                'credentials'        => [
                    'tokenApiUrl'  => 'https://prep-identity.kuveytturk.com.tr',
                    'accountUrl'   => 'https://prep-gateway.kuveytturk.com.tr',
                    'clientId'     => 'a77f21a3-fdf3-4121-8f5f-c7c9adc9a99d-fa635e68-eee3-4876-84e9-1cce7a0d2467',
                    'clientSecret' => '4dac270a-f0cd-47e5-92cd-bce339620842-c99f11b3-ef2f-49e5-9612-006aaebe2634',
                ],
            ],
            [
                'name'               => 'Ziraat',
                'is_active'          => true,
                'is_api_integration' => true,
                'credentials'        => null,
            ],
            [
                'name'               => 'Vakıf Üst',
                'is_active'          => true,
                'is_api_integration' => false,
                'credentials'        => null,
            ],
            [
                'name'               => 'Dış Kasa',
                'is_active'          => true,
                'is_api_integration' => false,
                'credentials'        => null,
            ],
        ];

        $createdGroups = [];
        foreach ($safeGroups as $groupData) {
            $group = SafeGroup::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name'       => $groupData['name'],
                ],
                [
                    'is_active'          => $groupData['is_active'],
                    'is_api_integration' => $groupData['is_api_integration'],
                    'credentials'        => $groupData['credentials'],
                    'created_user_id'    => $user->id,
                ]
            );
            $createdGroups[$groupData['name']] = $group;
        }

        // Safes
        $safes = [
            // Vakıf grubu
            [
                'name'                 => 'Ana Kasa TL',
                'safe_group_name'      => 'Vakıf',
                'currency_id'          => $tlId,
                'sort_order'           => 0,
            ],
            [
                'name'                 => 'Ana Kasa USD',
                'safe_group_name'      => 'Vakıf',
                'currency_id'          => $usdId,
                'sort_order'           => 1,
            ],
            [
                'name'                 => 'Ana Kasa EUR',
                'safe_group_name'      => 'Vakıf',
                'currency_id'          => $eurId,
                'sort_order'           => 2,
            ],
            // Vakıf Üst grubu
            [
                'name'                 => 'Üst Kasa TL',
                'safe_group_name'      => 'Vakıf Üst',
                'currency_id'          => $tlId,
                'sort_order'           => 3,
            ],
            [
                'name'                 => 'Üst Kasa USD',
                'safe_group_name'      => 'Vakıf Üst',
                'currency_id'          => $usdId,
                'sort_order'           => 4,
            ],
            [
                'name'                 => 'Üst Kasa EUR',
                'safe_group_name'      => 'Vakıf Üst',
                'currency_id'          => $eurId,
                'sort_order'           => 5,
            ],
            // Dış Kasa grubu
            [
                'name'                 => 'Dış Kasa EUR',
                'safe_group_name'      => 'Dış Kasa',
                'currency_id'          => $eurId,
                'sort_order'           => 100,
            ],
            // Ziraat grubu
            [
                'name'                 => 'Ziraat TL',
                'safe_group_name'      => 'Ziraat',
                'currency_id'          => $tlId,
                'sort_order'           => 99,
            ],
            [
                'name'                 => 'Ziraat USD',
                'safe_group_name'      => 'Ziraat',
                'currency_id'          => $usdId,
                'sort_order'           => 99,
            ],
            [
                'name'                 => 'Ziraat EUR',
                'safe_group_name'      => 'Ziraat',
                'currency_id'          => $eurId,
                'sort_order'           => 99,
            ],
            // Kuveyt Türk grubu
            [
                'name'                 => 'Kuveyt Türk Bağış TL',
                'safe_group_name'      => 'Kuveyt Türk',
                'currency_id'          => $tlId,
                'sort_order'           => 6,
            ],
            [
                'name'                 => 'Kuveyt Türk Zekat TL',
                'safe_group_name'      => 'Kuveyt Türk',
                'currency_id'          => $tlId,
                'sort_order'           => 9,
            ],
            [
                'name'                 => 'Kuveyt Türk USD',
                'safe_group_name'      => 'Kuveyt Türk',
                'currency_id'          => $usdId,
                'sort_order'           => 7,
            ],
            [
                'name'                 => 'Kuveyt Türk EUR',
                'safe_group_name'      => 'Kuveyt Türk',
                'currency_id'          => $eurId,
                'sort_order'           => 8,
            ],
            [
                'name'                 => 'Kuveyt Türk Katılım Bağış TL',
                'safe_group_name'      => 'Kuveyt Türk',
                'currency_id'          => $tlId,
                'sort_order'           => 20,
            ],
            [
                'name'                 => 'Kuveyt Türk Katılım Zekat TL',
                'safe_group_name'      => 'Kuveyt Türk',
                'currency_id'          => $tlId,
                'sort_order'           => 22,
            ],
            [
                'name'                 => 'Kuveyt Türk Hanımlar Kolu TL',
                'safe_group_name'      => 'Kuveyt Türk',
                'currency_id'          => $tlId,
                'sort_order'           => 23,
            ],
        ];

        foreach ($safes as $safeData) {
            $group = $createdGroups[$safeData['safe_group_name']];

            Safe::updateOrCreate(
                [
                    'company_id'     => $company->id,
                    'safe_group_id'  => $group->id,
                    'name'           => $safeData['name'],
                ],
                [
                    'iban'              => null,
                    'currency_id'       => $safeData['currency_id'],
                    'balance'           => 0.0000,
                    'is_active'         => true,
                    'sort_order'        => $safeData['sort_order'],
                    'last_processed_at' => null,
                    'integration_id'    => null,
                    'created_user_id'   => $user->id,
                ]
            );
        }

        // Tüm kasaları oluşturan user'a ata
        $safesInCompany = Safe::where('company_id', $company->id)->get();
        foreach ($safesInCompany as $safe) {
            $safe->users()->syncWithoutDetaching([$user->id]);
        }
    }
}
