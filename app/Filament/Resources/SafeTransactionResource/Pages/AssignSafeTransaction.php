<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Filament\Resources\SafeTransactionResource;
use App\Models\Safe;
use App\Models\SafeTransaction;
use App\Repositories\SafeTransactionRepository;
use App\Services\SafeTransactionService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class AssignSafeTransaction extends EditRecord
{
    protected static string $resource = SafeTransactionResource::class;

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $record = $this->getRecord();

        // Sadece kategori ID=3 (ATAMA BEKLİYOR) ve henüz atanmamış işlemler
        if (! $record->items()->where('transaction_category_id', 3)->exists() || $record->target_transaction_id !== null) {
            abort(403, 'Bu işlem atanamaz.');
        }
    }

    public function getTitle(): string
    {
        return 'İşlem Atama';
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Schemas\Components\Section::make('Kaynak İşlem')
                    ->description('Atama yapılacak işlem bilgileri')
                    ->schema([
                        Schemas\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('safe.name')
                                    ->label('Kasa')
                                    ->disabled(),

                                Forms\Components\TextInput::make('type_label')
                                    ->label('İşlem Türü')
                                    ->disabled(),

                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Tutar')
                                    ->disabled()
                                    ->suffix(fn (Model $record): string => $record->safe?->currency?->symbol ?? ''),
                            ]),

                        Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('transaction_date')
                                    ->label('Banka İşlem Zamanı')
                                    ->disabled(),

                                Forms\Components\TextInput::make('description')
                                    ->label('Açıklama')
                                    ->disabled(),
                            ]),
                    ]),

                Schemas\Components\Section::make('Atama')
                    ->description('Transfer veya döviz işlemi olarak atayın')
                    ->schema([
                        Forms\Components\Select::make('operation_choice')
                            ->label('Operasyon Türü')
                            ->options([
                                'transfer' => 'Para Transferi',
                                'exchange' => 'Döviz İşlemi',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set): void {
                                $set('target_safe_id', null);
                                $set('target_transaction_id', null);
                                $set('exchange_rate', null);
                                $set('target_amount', null);
                            }),

                        Forms\Components\Select::make('target_safe_id')
                            ->label('Hedef Kasa')
                            ->options(function (Get $get, Model $record): array {
                                $operationChoice = $get('operation_choice');

                                if ($operationChoice === null) {
                                    return [];
                                }

                                // Transfer: Aynı para birimindeki kasalar
                                if ($operationChoice === 'transfer') {
                                    return Safe::with('currency')
                                        ->where('currency_id', $record->safe->currency_id)
                                        ->where('id', '!=', $record->safe_id)
                                        ->get()
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }

                                // Exchange: Farklı para birimindeki kasalar
                                return Safe::with('currency')
                                    ->where('currency_id', '!=', $record->safe->currency_id)
                                    ->get()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set): void {
                                $set('target_transaction_id', null);
                            }),

                        Forms\Components\Select::make('target_transaction_id')
                            ->label('Hedef İşlem (API Kasası)')
                            ->helperText('Transfer ise tutar aynı olanlar listesinden seç, Döviz ise tüm kayıtlar listelenir')
                            ->options(function (Get $get, Model $record): array {
                                $operationChoice = $get('operation_choice');
                                $targetSafeId = $get('target_safe_id');

                                if ($operationChoice === null || $targetSafeId === null) {
                                    return [];
                                }

                                $targetSafe = Safe::with('safeGroup')->find($targetSafeId);

                                if ($targetSafe === null || ! $targetSafe->safeGroup->is_api_integration) {
                                    return [];
                                }

                                // Repository kullanarak eligible transactions'ı çek
                                $repository = app(SafeTransactionRepository::class);
                                $eligible = $repository->getEligibleTransactions($record, $targetSafe, $operationChoice);

                                return $eligible->mapWithKeys(fn (SafeTransaction $tx) => [
                                    $tx->id => sprintf(
                                        '%s - %s %s (%s)',
                                        $tx->transaction_date?->format('d.m.Y H:i') ?? 'N/A',
                                        number_format((float) $tx->total_amount, 2),
                                        $tx->safe->currency->symbol ?? '',
                                        $tx->type->label()
                                    ),
                                ])->toArray();
                            })
                            ->searchable()
                            ->visible(function (Get $get): bool {
                                $targetSafeId = $get('target_safe_id');

                                if ($targetSafeId === null) {
                                    return false;
                                }

                                $targetSafe = Safe::with('safeGroup')->find($targetSafeId);

                                return $targetSafe !== null && $targetSafe->safeGroup->is_api_integration;
                            })
                            ->required(function (Get $get): bool {
                                $targetSafeId = $get('target_safe_id');

                                if ($targetSafeId === null) {
                                    return false;
                                }

                                $targetSafe = Safe::with('safeGroup')->find($targetSafeId);

                                return $targetSafe !== null && $targetSafe->safeGroup->is_api_integration;
                            }),

                        Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('exchange_rate')
                                    ->label('Döviz Kuru')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.0001)
                                    ->required(function (Get $get): bool {
                                        $operationChoice = $get('operation_choice');
                                        $targetSafeId = $get('target_safe_id');

                                        if ($operationChoice !== 'exchange' || $targetSafeId === null) {
                                            return false;
                                        }

                                        $targetSafe = Safe::with('safeGroup')->find($targetSafeId);

                                        return $targetSafe !== null && ! $targetSafe->safeGroup->is_api_integration;
                                    })
                                    ->visible(function (Get $get): bool {
                                        $operationChoice = $get('operation_choice');
                                        $targetSafeId = $get('target_safe_id');

                                        if ($operationChoice !== 'exchange' || $targetSafeId === null) {
                                            return false;
                                        }

                                        $targetSafe = Safe::with('safeGroup')->find($targetSafeId);

                                        return $targetSafe !== null && ! $targetSafe->safeGroup->is_api_integration;
                                    }),

                                Forms\Components\TextInput::make('target_amount')
                                    ->label('Hedef Kasaya Girecek Tutar (Yabancı Para)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->required(function (Get $get): bool {
                                        $operationChoice = $get('operation_choice');
                                        $targetSafeId = $get('target_safe_id');

                                        if ($operationChoice !== 'exchange' || $targetSafeId === null) {
                                            return false;
                                        }

                                        $targetSafe = Safe::with('safeGroup')->find($targetSafeId);

                                        return $targetSafe !== null && ! $targetSafe->safeGroup->is_api_integration;
                                    })
                                    ->visible(function (Get $get): bool {
                                        $operationChoice = $get('operation_choice');
                                        $targetSafeId = $get('target_safe_id');

                                        if ($operationChoice !== 'exchange' || $targetSafeId === null) {
                                            return false;
                                        }

                                        $targetSafe = Safe::with('safeGroup')->find($targetSafeId);

                                        return $targetSafe !== null && ! $targetSafe->safeGroup->is_api_integration;
                                    }),
                            ]),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var SafeTransaction */
        $record = $this->getRecord();

        $data['type_label'] = $record->type->label();
        $data['operation_choice'] = null;
        $data['target_safe_id'] = null;
        $data['target_transaction_id'] = null;
        $data['exchange_rate'] = null;
        $data['target_amount'] = null;

        return $data;
    }

    protected function beforeSave(): void
    {
        $data = $this->form->getState();

        // Validasyon
        $operationChoice = $data['operation_choice'] ?? null;
        $targetSafeId = $data['target_safe_id'] ?? null;

        if ($operationChoice === null || $targetSafeId === null) {
            throw new \Exception('Operasyon türü ve hedef kasa seçilmelidir.');
        }

        $targetSafe = Safe::with('safeGroup')->find($targetSafeId);

        if ($targetSafe === null) {
            throw new \Exception('Hedef kasa bulunamadı.');
        }

        // API kasası için target_transaction_id kontrolü
        if ($targetSafe->safeGroup->is_api_integration && empty($data['target_transaction_id'])) {
            throw new \Exception('Hedef kasa API entegrasyonlu, hedef işlem seçilmelidir.');
        }

        // Döviz için exchange_rate ve target_amount kontrolü
        if ($operationChoice === 'exchange' && ! $targetSafe->safeGroup->is_api_integration) {
            if (empty($data['exchange_rate']) || empty($data['target_amount'])) {
                throw new \Exception('Döviz işleminde kur ve hedef tutar girilmelidir.');
            }
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SafeTransaction $record */
        app(SafeTransactionService::class)->assignTransaction($record, $data);

        Notification::make()
            ->success()
            ->title('İşlem Atandı')
            ->body('İşlem başarıyla atandı ve eşleştirildi.')
            ->send();

        return $record->refresh();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Geri')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}
