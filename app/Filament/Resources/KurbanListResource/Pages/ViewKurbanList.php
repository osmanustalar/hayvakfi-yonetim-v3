<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanListResource\Pages;

use App\Filament\Resources\KurbanListResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKurbanList extends ViewRecord
{
    protected static string $resource = KurbanListResource::class;

    public function getTitle(): string
    {
        return 'Kurban Listesi Görüntüle';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Düzenle'),
        ];
    }
}
