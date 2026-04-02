<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(
            ['name' => 'HAYVAKFI'],
            [
                'name' => 'HAYVAKFI',
                'tax_number' => '12345678901',
                'address' => null,
                'phone' => '+90 212 123 45 67',
                'is_active' => true,
            ]
        );
    }
}
