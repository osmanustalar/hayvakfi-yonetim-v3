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
        $data = $this->form->getState();

        if (empty($data['is_donor']) && empty($data['is_aid_recipient']) && empty($data['is_student'])) {
            Notification::make()
                ->danger()
                ->title('Kategori seçilmedi')
                ->body('En az bir kategori seçilmelidir: Bağışçı, Yardım Alan veya Öğrenci.')
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
