<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Filament\Resources\SafeTransactionResource;
use App\Models\SafeTransaction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSafeTransaction extends ViewRecord
{
    protected static string $resource = SafeTransactionResource::class;

    public function getTitle(): string
    {
        /** @var SafeTransaction $record */
        $record = $this->record;

        return 'Kasa İşlemi #' . $record->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Sil'),
        ];
    }
}
