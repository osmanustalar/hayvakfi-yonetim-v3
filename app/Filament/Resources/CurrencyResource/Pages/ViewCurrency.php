<?php

declare(strict_types=1);

namespace App\Filament\Resources\CurrencyResource\Pages;

use App\Filament\Resources\CurrencyResource;
use App\Models\Currency;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCurrency extends ViewRecord
{
    protected static string $resource = CurrencyResource::class;

    public function getTitle(): string
    {
        /** @var Currency $record */
        $record = $this->record;

        return $record->name.' ('.$record->symbol.')';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Düzenle'),
            DeleteAction::make()->label('Sil'),
        ];
    }
}
