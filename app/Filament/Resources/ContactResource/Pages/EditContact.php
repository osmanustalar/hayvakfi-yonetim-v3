<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Resources\ContactResource;
use App\Models\Contact;
use App\Services\ContactService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string
    {
        return 'Kişiyi Düzenle';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Sil'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ContactService $service */
        $service = app(ContactService::class);

        /** @var Contact $record */
        return $service->update($record, $data);
    }
}
