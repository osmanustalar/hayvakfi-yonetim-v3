<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactCategoryResource\Pages;

use App\Filament\Resources\ContactCategoryResource;
use App\Models\ContactCategory;
use App\Services\ContactCategoryService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditContactCategory extends EditRecord
{
    protected static string $resource = ContactCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Görüntüle'),
            DeleteAction::make()->label('Sil'),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ContactCategory $record */
        return app(ContactCategoryService::class)->update($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
