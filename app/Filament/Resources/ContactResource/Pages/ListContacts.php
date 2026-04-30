<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactResource\Pages;

use App\Exports\ContactsExport;
use App\Filament\Resources\ContactResource;
use App\Imports\ContactsImport;
use App\Traits\CustomTablePaginationTrait;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListContacts extends ListRecords
{
    use CustomTablePaginationTrait;

    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Yeni Kişi'),
            Action::make('exportContacts')
                ->label('Dışa Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(new ContactsExport(), 'kisiler-' . now()->format('Y-m-d') . '.xlsx')),
            Action::make('importContacts')
                ->label('Excel İçe Aktar')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel Dosyası')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->disk('local')
                        ->directory('imports/contacts')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $importer = new ContactsImport();
                    Excel::import($importer, Storage::disk('local')->path($data['file']));

                    Notification::make()
                        ->title('İçe Aktarma Tamamlandı')
                        ->body("{$importer->importedCount} yeni kayıt eklendi, {$importer->updatedCount} kayıt güncellendi, {$importer->skippedCount} satır atlandı.")
                        ->success()
                        ->send();

                    Storage::disk('local')->delete($data['file']);
                }),
        ];
    }
}
