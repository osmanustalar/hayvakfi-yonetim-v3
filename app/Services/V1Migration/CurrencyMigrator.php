<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

class CurrencyMigrator extends BaseMigrator
{
    /**
     * ID mapping: V1 currency ID -> V3 currency ID
     */
    public function getIdMap(): array
    {
        return $this->buildIdMap('currencies', 'currencies', 'name');
    }

    public function count(): int
    {
        return $this->v1()->table('currencies')->count();
    }

    public function migrate(bool $fresh = false): void
    {
        // V3'te zaten currencies var (DatabaseSeeder)
        // Fresh mode'da truncate ETMEYİZ çünkü SafeMigrator bunlara ihtiyaç duyacak
        // Sadece mapping oluşturulur
    }
}
