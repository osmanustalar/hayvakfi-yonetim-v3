<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactCategoryResource\Pages;

use App\Filament\Resources\ContactCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContactCategories extends ListRecords
{
    protected static string $resource = ContactCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Kategori'),
        ];
    }
}
