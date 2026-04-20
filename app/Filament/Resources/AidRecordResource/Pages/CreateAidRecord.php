<?php

declare(strict_types=1);

namespace App\Filament\Resources\AidRecordResource\Pages;

use App\Filament\Resources\AidRecordResource;
use App\Services\AidRecordService;
use Filament\Resources\Pages\CreateRecord;

class CreateAidRecord extends CreateRecord
{
    protected static string $resource = AidRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = (int) session('active_company_id');
        $data['created_user_id'] = auth()->id();

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(AidRecordService::class)->create($data);
    }
}
