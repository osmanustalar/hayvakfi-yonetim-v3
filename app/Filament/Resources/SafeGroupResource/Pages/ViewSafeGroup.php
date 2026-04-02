<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeGroupResource\Pages;

use App\Filament\Resources\SafeGroupResource;
use App\Models\SafeGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSafeGroup extends ViewRecord
{
    protected static string $resource = SafeGroupResource::class;

    public function getTitle(): string
    {
        /** @var SafeGroup $record */
        $record = $this->record;

        return $record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Düzenle'),
            DeleteAction::make()->label('Sil'),
        ];
    }
}
