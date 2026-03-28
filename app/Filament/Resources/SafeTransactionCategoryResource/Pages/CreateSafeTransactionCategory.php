<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionCategoryResource\Pages;

use App\Filament\Resources\SafeTransactionCategoryResource;
use App\Models\SafeTransactionCategory;
use App\Services\SafeTransactionCategoryService;
use Filament\Resources\Pages\CreateRecord;

class CreateSafeTransactionCategory extends CreateRecord
{
    protected static string $resource = SafeTransactionCategoryResource::class;

    public function getTitle(): string
    {
        return 'Yeni Kategori';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): SafeTransactionCategory
    {
        /** @var SafeTransactionCategoryService $service */
        $service = app(SafeTransactionCategoryService::class);

        return $service->create($data);
    }
}
