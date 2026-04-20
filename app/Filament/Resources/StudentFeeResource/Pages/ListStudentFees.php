<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentFeeResource\Pages;

use App\Filament\Resources\StudentFeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentFees extends ListRecords
{
    protected static string $resource = StudentFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
