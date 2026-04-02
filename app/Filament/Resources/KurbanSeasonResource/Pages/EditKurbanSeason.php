<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanSeasonResource\Pages;

use App\Filament\Resources\KurbanSeasonResource;
use App\Models\KurbanSeason;
use App\Services\KurbanSeasonService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKurbanSeason extends EditRecord
{
    protected static string $resource = KurbanSeasonResource::class;

    public function getTitle(): string
    {
        return 'Kurban Sezonunu Düzenle';
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
        /** @var KurbanSeasonService $service */
        $service = app(KurbanSeasonService::class);

        /** @var KurbanSeason $record */
        return $service->update($record, $data);
    }
}
