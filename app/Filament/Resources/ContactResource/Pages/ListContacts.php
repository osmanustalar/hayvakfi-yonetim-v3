<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Traits\CustomTablePaginationTrait;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContacts extends ListRecords
{
    use CustomTablePaginationTrait;

    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Kişi'),
        ];
    }
}
