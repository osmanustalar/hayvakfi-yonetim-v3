<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

abstract class BaseCreateRecord extends CreateRecord
{
    public function create(bool $another = false): void
    {
        $cacheKey = 'form_submit_' . auth()->id();

        if (Cache::has($cacheKey)) {
            Notification::make()
                ->warning()
                ->title('İşleminiz devam ediyor. Lütfen biraz bekleyin.')
                ->persistent()
                ->send();

            return;
        }

        Cache::put($cacheKey, true, 10);

        parent::create($another);
    }
}
