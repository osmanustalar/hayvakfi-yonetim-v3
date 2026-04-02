<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure super_admin role exists
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );

        // Get HAYVAKFI company
        $company = Company::where('name', 'HAYVAKFI')->first();

        if (! $company) {
            throw new \RuntimeException('HAYVAKFI company not found. Run CompanySeeder first.');
        }

        // Osman Ustalar
        $osmanUser = User::updateOrCreate(
            ['phone' => '05556260886'],
            [
                'name' => 'Osman Ustalar',
                'phone' => '05556260886',
                'password' => Hash::make('test'),
                'can_login' => true,
                'is_active' => true,
                'default_company_id' => $company->id,
            ]
        );

        // Attach to company (pivot)
        $osmanUser->companies()->syncWithoutDetaching([$company->id]);

        // Assign super_admin role
        if (! $osmanUser->hasRole('super_admin')) {
            $osmanUser->assignRole('super_admin');
        }

        // İsmail Türk
        $ismailUser = User::updateOrCreate(
            ['phone' => '05525567790'],
            [
                'name' => 'İsmail Türk',
                'phone' => '05525567790',
                'password' => Hash::make('0000'),
                'can_login' => true,
                'is_active' => true,
                'default_company_id' => $company->id,
            ]
        );

        // Attach to company (pivot)
        $ismailUser->companies()->syncWithoutDetaching([$company->id]);
    }
}
