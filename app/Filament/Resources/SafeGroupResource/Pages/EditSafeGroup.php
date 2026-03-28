<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeGroupResource\Pages;

use App\Filament\Resources\SafeGroupResource;
use App\Models\SafeGroup;
use App\Services\SafeGroupService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSafeGroup extends EditRecord
{
    protected static string $resource = SafeGroupResource::class;

    public function getTitle(): string
    {
        return 'Kasa Grubunu Düzenle';
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
        /** @var SafeGroupService $service */
        $service = app(SafeGroupService::class);

        /** @var SafeGroup $record */
        return $service->update($record, $data);
    }
}
