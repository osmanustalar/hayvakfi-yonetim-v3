<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanSeasonResource\Pages;

use App\Filament\Resources\KurbanSeasonResource;
use App\Models\KurbanSeason;
use App\Services\KurbanSeasonService;
use Filament\Resources\Pages\CreateRecord;

class CreateKurbanSeason extends CreateRecord
{
    protected static string $resource = KurbanSeasonResource::class;

    public function getTitle(): string
    {
        return 'Yeni Kurban Sezonu';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): KurbanSeason
    {
        /** @var KurbanSeasonService $service */
        $service = app(KurbanSeasonService::class);

        return $service->create($data);
    }
}
