<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactCategoryResource\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\ContactCategoryResource;
use App\Services\ContactCategoryService;
use Illuminate\Database\Eloquent\Model;

class CreateContactCategory extends BaseCreateRecord
{
    protected static string $resource = ContactCategoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ContactCategoryService::class)->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
