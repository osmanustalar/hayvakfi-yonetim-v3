<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class ShieldSeeder extends Seeder
{
    /**
     * Generate all Filament Shield permissions for resources, pages, and widgets.
     * Assign all permissions to the super_admin role.
     */
    public function run(): void
    {
        // Generate permissions only for resources (pages/widgets cause hangs with --all)
        Artisan::call('shield:generate', [
            '--all'    => true,
            '--option' => 'permissions',
            '--panel'  => 'admin',
        ]);

        // super_admin is handled via gate (define_via_gate: true in config),
        // so no need to sync permissions explicitly — just ensure the role exists.
        Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );
    }
}
