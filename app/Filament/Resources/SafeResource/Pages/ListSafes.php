<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeResource\Pages;

use App\Filament\Resources\SafeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSafes extends ListRecords
{
    protected static string $resource = SafeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Kasa'),
        ];
    }
}
