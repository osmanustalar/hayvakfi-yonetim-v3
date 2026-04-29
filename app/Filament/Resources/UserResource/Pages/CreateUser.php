<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Pages\BaseCreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends BaseCreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Yeni Kullanıcı';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $record = static::getModel()::create($data);

        if (!empty($roles)) {
            $record->syncRoles($roles);
        }

        return $record;
    }
}
