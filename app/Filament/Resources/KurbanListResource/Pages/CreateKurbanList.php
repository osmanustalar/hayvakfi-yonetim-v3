<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanListResource\Pages;

use App\Filament\Resources\KurbanListResource;
use App\Models\KurbanList;
use App\Services\KurbanListService;
use App\Filament\Pages\BaseCreateRecord;

class CreateKurbanList extends BaseCreateRecord
{
    protected static string $resource = KurbanListResource::class;

    public function getTitle(): string
    {
        return 'Yeni Kurban Listesi';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): KurbanList
    {
        /** @var KurbanListService $service */
        $service = app(KurbanListService::class);

        return $service->create($data);
    }
}
