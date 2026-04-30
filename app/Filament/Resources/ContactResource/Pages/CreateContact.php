<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Models\Contact;
use App\Services\ContactService;
use App\Filament\Pages\BaseCreateRecord;
use Filament\Notifications\Notification;

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

    protected function beforeCreate(): void
    {
        $categories = $this->form->getRawState()['categories'] ?? [];

        if (empty($categories)) {
            Notification::make()
                ->danger()
                ->title('Kategori seçilmedi')
                ->body('En az bir kategori seçilmelidir.')
                ->send();

            $this->halt();
        }
    }

    protected function handleRecordCreation(array $data): Contact
    {
        /** @var ContactService $service */
        $service = app(ContactService::class);

        return $service->create($data);
    }
}
