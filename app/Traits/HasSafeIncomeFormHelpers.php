<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ContactType;
use App\Models\Contact;
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
    private function buildContactOptions(ContactType $contactType, bool $skipTypeFilter = false): array
    {
        $query = Contact::query()->orderBy('first_name');

        if (! $skipTypeFilter) {
            $column = match ($contactType) {
                ContactType::DONOR => 'is_donor',
                ContactType::AID_RECIPIENT => 'is_aid_recipient',
                ContactType::STUDENT => 'is_student',
            };

            $query->where($column, true);
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
            $this->activeContactType = null;
            $this->activeIsKurban = false;

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

        $this->activeContactType = $category->contact_type;
        $this->activeIsKurban = (bool) ($category->is_sacrifice_type ?? false);
    }
}
