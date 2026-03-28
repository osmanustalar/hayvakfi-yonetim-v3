<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionCategoryResource\Pages;

use App\Filament\Resources\SafeTransactionCategoryResource;
use App\Models\SafeTransactionCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSafeTransactionCategory extends ViewRecord
{
    protected static string $resource = SafeTransactionCategoryResource::class;

    public function getTitle(): string
    {
        /** @var SafeTransactionCategory $record */
        $record = $this->record;

        return $record->name;
    }

    protected function getHeaderActions(): array
    {
        /** @var SafeTransactionCategory $record */
        $record = $this->record;

        $actions = [];

        if ($record->id > 5) {
            $actions[] = EditAction::make()->label('Düzenle');
            $actions[] = DeleteAction::make()->label('Sil');
        }

        return $actions;
    }
}
