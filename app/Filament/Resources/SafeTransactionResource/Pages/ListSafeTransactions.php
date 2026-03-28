<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Filament\Resources\SafeTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListSafeTransactions extends ListRecords
{
    protected static string $resource = SafeTransactionResource::class;

    protected function getHeaderActions(): array
    {
        // İşlem oluşturma SafeResource'daki hızlı butonlardan yapılır.
        return [];
    }
}
