<?php

declare(strict_types=1);

namespace App\Filament\Resources\SchoolClassResource\Pages;

use App\Filament\Resources\SchoolClassResource;
use App\Services\SchoolClassService;
use Filament\Resources\Pages\CreateRecord;

class CreateSchoolClass extends CreateRecord
{
    protected static string $resource = SchoolClassResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = (int) session('active_company_id');

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(SchoolClassService::class)->create($data);
    }
}
