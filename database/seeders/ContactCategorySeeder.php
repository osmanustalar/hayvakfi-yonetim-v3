<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContactType;
use App\Models\ContactCategory;
use Illuminate\Database\Seeder;

class ContactCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'contact_type' => ContactType::DONOR,
                'name'         => 'Bağışçı',
                'color'        => '#3B82F6',
                'is_active'    => true,
            ],
            [
                'contact_type' => ContactType::AID_RECIPIENT,
                'name'         => 'Yardım Alan',
                'color'        => '#10B981',
                'is_active'    => true,
            ],
            [
                'contact_type' => ContactType::STUDENT,
                'name'         => 'Öğrenci',
                'color'        => '#F59E0B',
                'is_active'    => true,
            ],
        ];

        foreach ($categories as $data) {
            ContactCategory::updateOrCreate(
                ['contact_type' => $data['contact_type']],
                $data
            );
        }
    }
}
