<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentFeeResource\Pages;

use App\Filament\Resources\StudentFeeResource;
use App\Services\StudentFeeService;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentFee extends CreateRecord
{
    protected static string $resource = StudentFeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = (int) session('active_company_id');

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(StudentFeeService::class)->create($data);
    }
}
