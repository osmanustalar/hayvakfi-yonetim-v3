<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeGroupResource\Pages;

use App\Filament\Resources\SafeGroupResource;
use App\Traits\CustomTablePaginationTrait;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSafeGroups extends ListRecords
{
    use CustomTablePaginationTrait;
    protected static string $resource = SafeGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Kasa Grubu'),
        ];
    }
}
