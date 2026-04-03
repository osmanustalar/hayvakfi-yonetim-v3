<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Pages\BaseCreateRecord;

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
}
