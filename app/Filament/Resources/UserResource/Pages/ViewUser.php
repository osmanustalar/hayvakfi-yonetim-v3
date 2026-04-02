<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        /** @var User $record */
        $record = $this->record;

        return $record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Düzenle'),
            DeleteAction::make()->label('Sil'),
        ];
    }
}
