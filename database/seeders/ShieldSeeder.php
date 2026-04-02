<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ShieldSeeder extends Seeder
{
    /**
     * Generate all Filament Shield permissions for resources, pages, and widgets.
     * Assign all permissions to the super_admin role.
     */
    public function run(): void
    {
        // Generate permissions and policies
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
        ]);

        // Get or create super_admin role
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );

        // Get all permissions and assign to super_admin
        $allPermissions = Permission::all();
        $superAdminRole->syncPermissions($allPermissions);
    }
}
