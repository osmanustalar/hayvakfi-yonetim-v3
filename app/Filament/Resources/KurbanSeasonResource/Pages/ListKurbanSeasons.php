<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanSeasonResource\Pages;

use App\Filament\Resources\KurbanSeasonResource;
use App\Traits\CustomTablePaginationTrait;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKurbanSeasons extends ListRecords
{
    use CustomTablePaginationTrait;
    protected static string $resource = KurbanSeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Kurban Sezonu'),
        ];
    }
}
