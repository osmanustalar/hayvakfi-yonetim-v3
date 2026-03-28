<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeResource\Pages;

use App\Filament\Resources\SafeResource;
use App\Models\Safe;
use App\Services\SafeService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSafe extends EditRecord
{
    protected static string $resource = SafeResource::class;

    public function getTitle(): string
    {
        return 'Kasayı Düzenle';
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
        /** @var SafeService $service */
        $service = app(SafeService::class);

        /** @var Safe $record */
        return $service->update($record, $data);
    }
}
