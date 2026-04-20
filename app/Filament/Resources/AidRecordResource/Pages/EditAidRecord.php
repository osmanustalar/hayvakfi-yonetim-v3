<?php

declare(strict_types=1);

namespace App\Filament\Resources\AidRecordResource\Pages;

use App\Filament\Resources\AidRecordResource;
use App\Services\AidRecordService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAidRecord extends EditRecord
{
    protected static string $resource = AidRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        app(AidRecordService::class)->update($record->id, $data);

        return $record->fresh();
    }
}
