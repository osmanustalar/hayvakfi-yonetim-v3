<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Filament\Resources\SafeTransactionResource;
use App\Services\SafeTransactionService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSafeTransaction extends CreateRecord
{
    protected static string $resource = SafeTransactionResource::class;

    public function getTitle(): string
    {
        return 'Yeni Kasa İşlemi';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var SafeTransactionService $service */
        $service = app(SafeTransactionService::class);

        try {
            $operationType = $data['operation_type'] ?? null;

            if ($operationType === 'transfer') {
                $transferData = [
                    'source_safe_id' => $data['safe_id'],
                    'target_safe_id' => $data['target_safe_id'],
                    'amount'         => $data['amount'],
                    'process_date'   => $data['process_date'],
                    'description'    => $data['description'] ?? null,
                ];
                $result = $service->createTransfer($transferData);

                return $result['source'];
            }

            if ($operationType === 'exchange') {
                return $service->createExchange($data);
            }

            return $service->create($data);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('İşlem Hatası')
                ->body($e->getMessage())
                ->send();

            $this->halt();
        }
    }
}
