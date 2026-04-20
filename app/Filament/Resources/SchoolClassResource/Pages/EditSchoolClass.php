<?php

declare(strict_types=1);

namespace App\Filament\Resources\SchoolClassResource\Pages;

use App\Filament\Resources\SchoolClassResource;
use App\Services\SchoolClassService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolClass extends EditRecord
{
    protected static string $resource = SchoolClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        app(SchoolClassService::class)->update($record->id, $data);

        return $record->fresh();
    }
}
