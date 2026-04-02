<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Filament\Resources\SafeTransactionResource;
use App\Traits\CustomTablePaginationTrait;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListSafeTransactions extends ListRecords
{
    use CustomTablePaginationTrait;

    protected static string $resource = SafeTransactionResource::class;

    public function mount(): void
    {
        parent::mount();

        // URL'den safe_id parametresi varsa, filtreyi otomatik uygula
        if (request()->has('safe_id')) {
            $this->tableFilters['safe_id'] = [
                'value' => request('safe_id'),
            ];
        }
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        // Arama kutusu için safe.name ve description alanlarında arama yap
        if ($search = $this->getTableSearch()) {
            $query->where(function (Builder $q) use ($search): Builder {
                return $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('safe', fn (Builder $subQ): Builder => $subQ->where('name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    protected function getHeaderActions(): array
    {
        // İşlem oluşturma SafeResource'daki hızlı butonlardan yapılır.
        return [];
    }
}
