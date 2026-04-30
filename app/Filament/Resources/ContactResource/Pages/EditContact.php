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
        // Relationship alanları getState()'e gelmez; form raw state'ten alıyoruz
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ContactService $service */
        $service = app(ContactService::class);

        /** @var Contact $record */
        return $service->update($record, $data);
    }
}
