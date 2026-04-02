<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanListResource\Pages;

use App\Filament\Resources\KurbanListResource;
use App\Traits\CustomTablePaginationTrait;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKurbanLists extends ListRecords
{
    use CustomTablePaginationTrait;

    protected static string $resource = KurbanListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Liste'),
        ];
    }
}
