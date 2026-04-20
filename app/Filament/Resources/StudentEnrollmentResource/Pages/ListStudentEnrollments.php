<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentEnrollmentResource\Pages;

use App\Filament\Resources\StudentEnrollmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentEnrollments extends ListRecords
{
    protected static string $resource = StudentEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
