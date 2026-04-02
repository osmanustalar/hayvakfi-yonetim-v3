<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanEntryResource\Pages;

use App\Filament\Resources\KurbanEntryResource;
use App\Models\KurbanEntry;
use App\Services\KurbanEntryService;
use Filament\Resources\Pages\CreateRecord;

class CreateKurbanEntry extends CreateRecord
{
    protected static string $resource = KurbanEntryResource::class;

    protected function handleRecordCreation(array $data): KurbanEntry
    {
        return app(KurbanEntryService::class)->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
