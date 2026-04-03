<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Filament\Pages\BaseCreateRecord;

class CreateCompany extends BaseCreateRecord
{
    protected static string $resource = CompanyResource::class;

    public function getTitle(): string
    {
        return 'Yeni Şirket';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
