<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Filament\Resources\SafeTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListSafeTransactions extends ListRecords
{
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

    protected function getHeaderActions(): array
    {
        // İşlem oluşturma SafeResource'daki hızlı butonlardan yapılır.
        return [];
    }
}
