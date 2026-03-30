<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SafeGroup;
use Illuminate\Database\Seeder;

class SafeGroupSeeder extends Seeder
{
    public function run(): void
    {
        // KuveytTürk — API entegrasyon kasası
        SafeGroup::updateOrCreate(
            ['name' => 'KuveytTürk'],
            [
                'company_id'         => 1,
                'is_active'          => true,
                'is_api_integration' => true,
                'credentials'        => null,
                'created_user_id'    => 1,
            ]
        );

        // Ziraat Bankası — API entegrasyon kasası
        SafeGroup::updateOrCreate(
            ['name' => 'Ziraat Bankası'],
            [
                'company_id'         => 1,
                'is_active'          => true,
                'is_api_integration' => true,
                'credentials'        => null,
                'created_user_id'    => 1,
            ]
        );
    }
}
