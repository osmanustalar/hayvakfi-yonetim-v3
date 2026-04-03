<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Models\Contact;
use App\Services\ContactService;
use App\Filament\Pages\BaseCreateRecord;

class CreateContact extends BaseCreateRecord
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string
    {
        return 'Yeni Kişi';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Contact
    {
        /** @var ContactService $service */
        $service = app(ContactService::class);

        return $service->create($data);
    }
}
