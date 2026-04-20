<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentFeeResource\Pages;

use App\Filament\Resources\StudentFeeResource;
use App\Services\StudentFeeService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStudentFee extends EditRecord
{
    protected static string $resource = StudentFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        app(StudentFeeService::class)->update($record->id, $data);

        return $record->fresh();
    }
}
