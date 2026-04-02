<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanSeasonResource\Pages;

use App\Filament\Resources\KurbanSeasonResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKurbanSeason extends ViewRecord
{
    protected static string $resource = KurbanSeasonResource::class;

    public function getTitle(): string
    {
        return 'Kurban Sezonu Detayı';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Düzenle'),
        ];
    }
}
