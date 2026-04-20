<?php

declare(strict_types=1);

namespace App\Filament\Resources\SchoolClassResource\Pages;

use App\Filament\Resources\SchoolClassResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchoolClasses extends ListRecords
{
    protected static string $resource = SchoolClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
