<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentEnrollmentResource\Pages;

use App\Filament\Resources\StudentEnrollmentResource;
use App\Services\StudentEnrollmentService;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentEnrollment extends CreateRecord
{
    protected static string $resource = StudentEnrollmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = (int) session('active_company_id');

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(StudentEnrollmentService::class)->create($data);
    }
}
