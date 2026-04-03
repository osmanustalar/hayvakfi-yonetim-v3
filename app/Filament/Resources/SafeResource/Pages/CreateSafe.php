<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeResource\Pages;

use App\Filament\Resources\SafeResource;
use App\Models\Safe;
use App\Services\SafeService;
use App\Filament\Pages\BaseCreateRecord;

class CreateSafe extends BaseCreateRecord
{
    protected static string $resource = SafeResource::class;

    public function getTitle(): string
    {
        return 'Yeni Kasa';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Safe
    {
        /** @var SafeService $service */
        $service = app(SafeService::class);

        return $service->create($data);
    }
}
