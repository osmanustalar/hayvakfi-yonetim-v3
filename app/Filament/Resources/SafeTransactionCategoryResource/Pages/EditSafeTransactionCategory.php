<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionCategoryResource\Pages;

use App\Filament\Resources\SafeTransactionCategoryResource;
use App\Models\SafeTransactionCategory;
use App\Services\SafeTransactionCategoryService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSafeTransactionCategory extends EditRecord
{
    protected static string $resource = SafeTransactionCategoryResource::class;

    public function getTitle(): string
    {
        return 'Kategoriyi Düzenle';
    }

    protected function authorizeAccess(): void
    {
        /** @var SafeTransactionCategory $record */
        $record = $this->getRecord();

        abort_unless($record->id > 5, 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Sil'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SafeTransactionCategoryService $service */
        $service = app(SafeTransactionCategoryService::class);

        /** @var SafeTransactionCategory $record */
        return $service->update($record, $data);
    }
}
