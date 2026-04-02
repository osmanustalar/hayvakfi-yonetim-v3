<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanEntryResource\Pages;

use App\Filament\Resources\KurbanEntryResource;
use App\Services\KurbanEntryService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKurbanEntry extends EditRecord
{
    protected static string $resource = KurbanEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Sil'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(KurbanEntryService::class)->update($record, $data);
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
