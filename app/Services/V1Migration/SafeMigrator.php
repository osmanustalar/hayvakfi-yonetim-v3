<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

class SafeMigrator extends BaseMigrator
{
    private array $currencyMap = [];

    public function __construct(private CurrencyMigrator $currencyMigrator) {}

    public function count(): int
    {
        return $this->v1()->table('safes')->count();
    }

    public function migrate(bool $fresh = false): void
    {
        $this->currencyMap = $this->currencyMigrator->getIdMap();

        if ($fresh) {
            $this->truncate('safes');
        }

        $companyId = $this->v3()->table('companies')->first()?->id ?? 1;
        $v1Safes = $this->v1()->table('safes')->get();

        foreach ($v1Safes as $v1Safe) {
            $currencyId = $v1Safe->currency_id ? ($this->currencyMap[$v1Safe->currency_id] ?? $v1Safe->currency_id) : null;

            $existing = $this->v3()->table('safes')->where('id', $v1Safe->id)->first();
            if ($existing) {
                $this->v3()->table('safes')->where('id', $v1Safe->id)->update([
                    'company_id' => $companyId,
                    'name' => $v1Safe->name,
                    'balance' => $v1Safe->balance,
                    'currency_id' => $currencyId,
                    'safe_group_id' => $v1Safe->safe_group_id,
                    'is_active' => (bool) $v1Safe->is_active,
                    'sort_order' => $v1Safe->sort_order ?? 99,
                    'last_processed_at' => $v1Safe->last_processed_at,
                    'created_user_id' => $v1Safe->created_user_id,
                    'updated_at' => $v1Safe->updated_at,
                ]);
            } else {
                $this->v3()->table('safes')->insert([
                    'id' => $v1Safe->id,
                    'company_id' => $companyId,
                    'name' => $v1Safe->name,
                    'balance' => $v1Safe->balance,
                    'currency_id' => $currencyId,
                    'safe_group_id' => $v1Safe->safe_group_id,
                    'is_active' => (bool) $v1Safe->is_active,
                    'sort_order' => $v1Safe->sort_order ?? 99,
                    'last_processed_at' => $v1Safe->last_processed_at,
                    'created_user_id' => $v1Safe->created_user_id,
                    'created_at' => $v1Safe->created_at,
                    'updated_at' => $v1Safe->updated_at,
                    'deleted_at' => $v1Safe->deleted_at,
                ]);
            }
        }
    }
}
