<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanListResource\Pages;

use App\Filament\Resources\KurbanListResource;
use App\Models\KurbanList;
use App\Services\KurbanListService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKurbanList extends EditRecord
{
    protected static string $resource = KurbanListResource::class;

    public function getTitle(): string
    {
        return 'Kurban Listesini Düzenle';
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var KurbanListService $service */
        $service = app(KurbanListService::class);

        /** @var KurbanList $record */
        return $service->update($record, $data);
    }
}
