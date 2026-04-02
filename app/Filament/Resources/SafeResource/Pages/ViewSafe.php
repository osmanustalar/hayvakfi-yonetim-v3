<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeResource\Pages;

use App\Filament\Resources\SafeResource;
use App\Models\Safe;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSafe extends ViewRecord
{
    protected static string $resource = SafeResource::class;

    public function getTitle(): string
    {
        /** @var Safe $record */
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
