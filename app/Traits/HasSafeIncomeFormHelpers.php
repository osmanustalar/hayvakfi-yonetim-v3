<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Contact;
use App\Models\ContactCategory;
use App\Models\SafeTransactionCategory;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;

trait HasSafeIncomeFormHelpers
{
    /**
     * @return array<int|string, string>
     */
    private static function buildCategoryOptions(string $type): array
    {
        $parents = SafeTransactionCategory::query()
            ->forActiveCompany()
            ->where('type', $type)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $options = [];

        foreach ($parents as $parent) {
            $children = SafeTransactionCategory::query()
                ->forActiveCompany()
                ->where('type', $type)
                ->where('is_active', true)
                ->where('parent_id', $parent->id)
                ->orderBy('sort_order')
                ->get();

            if ($children->isEmpty()) {
                $options[$parent->id] = $parent->name;
            } else {
                $options[$parent->id] = $parent->name.' (Seçilemez)';

                foreach ($children as $child) {
                    $options[$child->id] = '⤷ '.$parent->name.' → '.$child->name;
                }
            }
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private function buildContactOptions(?int $contactCategoryId, bool $skipTypeFilter = false): array
    {
        $query = Contact::query()->orderBy('first_name');

        if (! $skipTypeFilter && $contactCategoryId !== null) {
            $query->whereHas('categories', fn ($q) => $q->where('contact_categories.id', $contactCategoryId));
        }

        return $query
            ->with('region')
            ->get()
            ->mapWithKeys(fn (Contact $c): array => [
                $c->id => $c->first_name.' '.$c->last_name
                    .($c->phone ? ' — '.$c->phone : '')
                    .($c->region ? ' — '.$c->region->name : ''),
            ])
            ->toArray();
    }

    protected function handleCategoryStateUpdated(?int $state, Set $set): void
    {
        if ($state === null) {
            $this->activeContactCategoryId    = null;
            $this->activeContactCategoryLabel = 'İlgili Kişi';
            $this->activeIsKurban             = false;

            return;
        }

        $category = SafeTransactionCategory::find($state);

        if ($category === null) {
            return;
        }

        if ($category->children()->exists()) {
            $set('transaction_category_id', null);
            Notification::make()
                ->danger()
                ->title('Kategori Seçimi Engellendi')
                ->body('Alt kategorisi olan ana kategori seçilemez. Lütfen bir alt kategori seçin.')
                ->send();

            return;
        }

        $this->activeContactCategoryId    = $category->contact_category_id;
        $this->activeContactCategoryLabel = $category->contactCategory?->name ?? 'İlgili Kişi';
        $this->activeIsKurban             = (bool) ($category->is_sacrifice_type ?? false);
    }
}
