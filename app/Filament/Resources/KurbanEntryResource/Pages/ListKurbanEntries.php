<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanEntryResource\Pages;

use App\Filament\Resources\KurbanEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKurbanEntries extends ListRecords
{
    protected static string $resource = KurbanEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Yeni Kayıt'),
        ];
    }
}
