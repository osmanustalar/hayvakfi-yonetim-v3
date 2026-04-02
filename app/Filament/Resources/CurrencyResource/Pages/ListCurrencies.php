<?php

declare(strict_types=1);

namespace App\Filament\Resources\CurrencyResource\Pages;

use App\Filament\Resources\CurrencyResource;
use App\Traits\CustomTablePaginationTrait;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCurrencies extends ListRecords
{
    use CustomTablePaginationTrait;

    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Para Birimi'),
        ];
    }
}
