<?php

declare(strict_types=1);

namespace App\Filament\Resources\AidRecordResource\Pages;

use App\Filament\Resources\AidRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAidRecords extends ListRecords
{
    protected static string $resource = AidRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
