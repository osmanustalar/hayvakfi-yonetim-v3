<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactCategoryResource\Pages;

use App\Filament\Resources\ContactCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactCategory extends ViewRecord
{
    protected static string $resource = ContactCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Düzenle'),
        ];
    }
}
