<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

use Illuminate\Support\Facades\DB;

abstract class BaseMigrator
{
    protected string $v1Connection = 'v1';

    protected string $v3Connection = 'mysql';

    /**
     * Kaç kayıt migrate edileceğini döner
     */
    abstract public function count(): int;

    /**
     * Gerçek migration işlemini yapar
     */
    abstract public function migrate(bool $fresh = false): void;

    /**
     * V1 DB'ye bağlantısı döner
     */
    protected function v1()
    {
        return DB::connection($this->v1Connection);
    }

    /**
     * V3 DB'ye bağlantısı döner
     */
    protected function v3()
    {
        return DB::connection($this->v3Connection);
    }

    /**
     * Tablonun tüm kayıtlarını temizler (V3'te)
     */
    protected function truncate(string $table): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement("TRUNCATE TABLE {$table}");
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * ID mapping tablosu oluşturur (örn: currencies v1_id -> v3_id)
     */
    protected function buildIdMap(string $v1Table, string $v3Table, string $field): array
    {
        $map = [];
        $v1Records = $this->v1()->table($v1Table)->get();
        foreach ($v1Records as $record) {
            $v3Record = $this->v3()
                ->table($v3Table)
                ->where($field, $record->name ?? $record->{$field} ?? null)
                ->first();
            if ($v3Record) {
                $map[$record->id] = $v3Record->id;
            }
        }

        return $map;
    }
}
