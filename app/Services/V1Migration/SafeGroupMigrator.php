<?php

declare(strict_types=1);

namespace App\Services\V1Migration;

class SafeGroupMigrator extends BaseMigrator
{
    public function count(): int
    {
        return $this->v1()->table('safe_groups')->count();
    }

    public function migrate(bool $fresh = false): void
    {
        if ($fresh) {
            $this->truncate('safe_groups');
        }

        $companyId = $this->v3()->table('companies')->first()?->id ?? 1;
        $v1Groups = $this->v1()->table('safe_groups')->get();

        foreach ($v1Groups as $v1Group) {
            // ID'yi korumak için direct query kullan
            $existing = $this->v3()->table('safe_groups')->where('id', $v1Group->id)->first();

            // "Kuveyt Türk" ve "Ziraat" grupları API entegrasyonlu olarak işaretle
            $isApiIntegration = in_array($v1Group->name, ['Kuveyt Türk', 'Ziraat']);

            if ($existing) {
                // Update var olan kaydı
                $this->v3()->table('safe_groups')->where('id', $v1Group->id)->update([
                    'company_id' => $companyId,
                    'name' => $v1Group->name,
                    'is_active' => (bool) $v1Group->is_active,
                    'is_api_integration' => $isApiIntegration,
                    'created_user_id' => $v1Group->created_user_id,
                    'updated_at' => $v1Group->updated_at,
                ]);
            } else {
                // Insert yeni kaydı ID'yi tutarak
                $this->v3()->table('safe_groups')->insert([
                    'id' => $v1Group->id,
                    'company_id' => $companyId,
                    'name' => $v1Group->name,
                    'is_active' => (bool) $v1Group->is_active,
                    'is_api_integration' => $isApiIntegration,
                    'created_user_id' => $v1Group->created_user_id,
                    'created_at' => $v1Group->created_at,
                    'updated_at' => $v1Group->updated_at,
                    'deleted_at' => $v1Group->deleted_at,
                ]);
            }
        }
    }
}
