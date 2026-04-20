<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentEnrollmentResource\Pages;

use App\Filament\Resources\StudentEnrollmentResource;
use App\Services\StudentEnrollmentService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentEnrollment extends EditRecord
{
    protected static string $resource = StudentEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        app(StudentEnrollmentService::class)->update($record->id, $data);

        return $record->fresh();
    }
}
