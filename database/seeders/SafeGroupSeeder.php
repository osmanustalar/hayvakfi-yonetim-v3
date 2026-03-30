<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SafeGroup;
use Illuminate\Database\Seeder;

class SafeGroupSeeder extends Seeder
{
    public function run(): void
    {
        // Kuveyt Türk — SafeSeeder tarafından oluşturulan grubu API entegrasyonlu olarak işaretle
        SafeGroup::updateOrCreate(
            ['name' => 'Kuveyt Türk'],
            [
                'is_api_integration' => true,
            ]
        );

        // Ziraat — SafeSeeder tarafından oluşturulan grubu API entegrasyonlu olarak işaretle
        SafeGroup::updateOrCreate(
            ['name' => 'Ziraat'],
            [
                'is_api_integration' => true,
            ]
        );
    }
}
