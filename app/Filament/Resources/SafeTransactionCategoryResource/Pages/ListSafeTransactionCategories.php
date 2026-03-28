<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionCategoryResource\Pages;

use App\Filament\Resources\SafeTransactionCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSafeTransactionCategories extends ListRecords
{
    protected static string $resource = SafeTransactionCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Kategori'),
        ];
    }
}
