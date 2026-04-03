<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeGroupResource\Pages;

use App\Filament\Resources\SafeGroupResource;
use App\Models\SafeGroup;
use App\Services\SafeGroupService;
use App\Filament\Pages\BaseCreateRecord;

class CreateSafeGroup extends BaseCreateRecord
{
    protected static string $resource = SafeGroupResource::class;

    public function getTitle(): string
    {
        return 'Yeni Kasa Grubu';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): SafeGroup
    {
        /** @var SafeGroupService $service */
        $service = app(SafeGroupService::class);

        return $service->create($data);
    }
}
