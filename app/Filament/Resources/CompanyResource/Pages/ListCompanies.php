<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use App\Traits\CustomTablePaginationTrait;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    use CustomTablePaginationTrait;

    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Şirket'),
        ];
    }
}
