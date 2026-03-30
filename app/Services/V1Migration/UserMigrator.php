<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

use App\Models\User;

class UserMigrator extends BaseMigrator
{
    public function count(): int
    {
        return $this->v1()->table('users')->count();
    }

    public function migrate(bool $fresh = false): void
    {
        if ($fresh) {
            $this->truncate('users');
        }

        $v1Users = $this->v1()->table('users')->get();
        $defaultCompanyId = $this->v3()->table('companies')->first()?->id ?? 1;

        foreach ($v1Users as $v1User) {
            $phone = ($v1User->phone_code ?? '+90') . ($v1User->phone_number ?? '');

            // Password NULL ise dummy hash oluştur (kullanıcı şifre sıfırlamalı)
            $password = $v1User->password ?? bcrypt('password-to-reset-' . now()->timestamp);

            User::updateOrCreate(
                ['id' => $v1User->id],
                [
                    'name' => $v1User->name,
                    'phone' => $phone,
                    'password' => $password,
                    'can_login' => (bool) $v1User->is_login,
                    'is_active' => (bool) $v1User->is_active,
                    'default_company_id' => $defaultCompanyId,
                    'created_at' => $v1User->created_at,
                    'updated_at' => $v1User->updated_at,
                    'deleted_at' => $v1User->deleted_at,
                ]
            );
        }
    }
}
