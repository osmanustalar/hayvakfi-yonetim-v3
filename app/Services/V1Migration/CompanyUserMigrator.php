<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

use Illuminate\Support\Facades\DB;

class CompanyUserMigrator extends BaseMigrator
{
    public function count(): int
    {
        return $this->v1()->table('users')->count();
    }

    public function migrate(bool $fresh = false): void
    {
        if ($fresh) {
            $this->truncate('company_user');
        }

        $companyId = $this->v3()->table('companies')->first()?->id ?? 1;
        $v1Users = $this->v1()->table('users')->pluck('id');

        foreach ($v1Users as $userId) {
            DB::table('company_user')->insertOrIgnore([
                'company_id' => $companyId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
