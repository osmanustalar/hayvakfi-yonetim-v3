<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Enums\ContactType;
use App\Enums\OperationType;
use App\Enums\TransactionType;
use App\Filament\Resources\SafeTransactionResource;
use App\Models\KurbanEntry;
use App\Models\Safe;
use App\Models\SafeTransaction;
use App\Repositories\SafeTransactionRepository;
use App\Services\SafeTransactionService;
use App\Traits\HasSafeIncomeFormHelpers;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class WizardAssignSafeTransaction extends EditRecord
{
    use HasSafeIncomeFormHelpers;

    protected static string $resource = SafeTransactionResource::class;

    public ?ContactType $activeContactType = null;

    public bool $activeIsKurban = false;

    protected array $cachedTargetSafes = [];

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $record = $this->getRecord();

        $hasAwaitingCategory = $record->items()->where('transaction_category_id', 3)->exists();
        $hasUnpairedOperation = $record->operation_type !== null && $record->target_transaction_id === null;

        if (! $hasAwaitingCategory && ! $hasUnpairedOperation) {
            abort(403, 'Bu işlem atama sihirbazıyla işlenemez.');
        }
    }

    public function getTitle(): string
    {
        return 'İşlem Atama Sihirbazı';
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Tür Seçimi')
                        ->schema([
                            Section::make('İşlem Bilgileri')
                                ->description('Mevcut işlem bilgileri (değiştirilemez)')
                                ->icon('heroicon-o-information-circle')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('_safe_name')
                                                ->label('Kasa')
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('_type_label')
                                                ->label('İşlem Türü')
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('_total_amount')
                                                ->label('Tutar')
                                                ->disabled()
                                                ->dehydrated(false),
                                        ]),

                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('_transaction_date')
                                                ->label('Banka İşlem Zamanı')
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('_description')
                                                ->label('Açıklama')
                                                ->disabled()
                                                ->dehydrated(false),
                                        ]),
                                ]),

                            Section::make('İşlem Türü')
                                ->description('Bu işlemi nasıl sınıflandırmak istersiniz?')
                                ->icon('heroicon-o-clipboard-document-check')
                                ->schema([
                                    Radio::make('operation_type')
                                        ->label('İşlem Türünü Seçin')
                                        ->required()
                                        ->live()
                                        ->options([
                                            'normal' => 'Normal Giriş / Çıkış İşlemi',
                                            'exchange' => 'Döviz İşlemi',
                                            'transfer' => 'Para Transferi',
                                        ])
                                        ->descriptions([
                                            'normal' => 'Kategori atayarak gelir/gider olarak kayıt et',
                                            'exchange' => 'Farklı para birimindeki bir kasayla döviz eşleştirmesi yap',
                                            'transfer' => 'Aynı para birimindeki başka bir kasaya transfer olarak işle',
                                        ])
                                        ->afterStateUpdated(function (Set $set): void {
                                            $set('transaction_category_id', null);
                                            $set('kurban_entry_id', null);
                                            $set('contact_id', null);
                                            $set('target_safe_id', null);
                                            $set('target_transaction_id', null);
                                            $set('exchange_rate', null);
                                            $set('target_amount', null);

                                            $this->activeContactType = null;
                                            $this->activeIsKurban = false;
                                        }),
                                ]),
                        ]),

                    Step::make('İşlem Bilgileri')
                        ->schema([
                            Section::make('İşlem Bilgileri')
                                ->description('Mevcut işlem bilgileri (değiştirilemez)')
                                ->icon('heroicon-o-information-circle')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('_safe_name')
                                                ->label('Kasa')
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('_type_label')
                                                ->label('İşlem Türü')
                                                ->disabled()
                                                ->dehydrated(false),

                                            TextInput::make('_total_amount')
                                                ->label('Tutar')
                                                ->disabled()
                                                ->dehydrated(false),
                                        ]),

                                    TextInput::make('description')
                                        ->label('Açıklama')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Kategori')
                                ->description('İşlem kategorisi seçin')
                                ->icon('heroicon-o-tag')
                                ->visible(fn (Get $get): bool => $get('operation_type') === 'normal')
                                ->schema([
                                    Select::make('transaction_category_id')
                                        ->label('Kategori')
                                        ->options(fn (): array => self::buildCategoryOptions($this->getRecordType()))
                                        ->required(fn (Get $get): bool => $get('operation_type') === 'normal')
                                        ->live()
                                        ->afterStateUpdated(fn (?int $state, Set $set) => $this->handleCategoryStateUpdated($state, $set))
                                        ->searchable()
                                        ->prefixIcon('heroicon-o-tag')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Kurban Kaydı')
                                ->description('Kurban kategorisi seçildiyse listeden bir kayıt seçebilirsiniz')
                                ->icon('heroicon-o-user-group')
                                ->visible(fn (): bool => $this->activeIsKurban)
                                ->schema([
                                    Select::make('kurban_entry_id')
                                        ->label('Kurban Listesinden Seç')
                                        ->placeholder('Ödenmemiş kurban kaydı seçin (opsiyonel)')
                                        ->options(function (): array {
                                            $entries = KurbanEntry::query()
                                                ->where('company_id', session('active_company_id'))
                                                ->where('is_paid', false)
                                                ->whereNull('safe_transaction_id')
                                                ->with(['list.season', 'list.collector', 'contact'])
                                                ->get();

                                            return $entries->mapWithKeys(function (KurbanEntry $entry): array {
                                                $seasonYear = $entry->list?->season?->year ?? '?';
                                                $collectorName = $entry->list?->collector?->name ?? '?';

                                                return [
                                                    $entry->id => sprintf(
                                                        '%s %s — %s / %s',
                                                        $entry->contact?->first_name ?? '?',
                                                        $entry->contact?->last_name ?? '?',
                                                        $seasonYear,
                                                        $collectorName
                                                    ),
                                                ];
                                            })->toArray();
                                        })
                                        ->searchable()
                                        ->nullable()
                                        ->helperText('Kurban kategorisi seçildiğinde ödenmemiş kayıtlar burada listelenir.')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('İlgili Kişi')
                                ->description('İşlemle ilişkili kişi bilgisi')
                                ->icon('heroicon-o-user-circle')
                                ->visible(fn (): bool => $this->activeContactType !== null)
                                ->schema([
                                    Select::make('contact_id')
                                        ->label(fn (): string => $this->activeContactType?->label() ?? 'İlgili Kişi')
                                        ->options(fn (): array => $this->buildContactOptions($this->activeContactType, $this->activeIsKurban))
                                        ->searchable()
                                        ->prefixIcon('heroicon-o-user-group')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Hedef Kasa Seçimi')
                                ->description('Transfer veya döviz işlemi için hedef kasa')
                                ->icon('heroicon-o-building-library')
                                ->visible(fn (Get $get): bool => in_array($get('operation_type'), ['exchange', 'transfer'], true))
                                ->schema([
                                    Select::make('target_safe_id')
                                        ->label('Hedef Kasa')
                                        ->options(function (Get $get): array {
                                            $operationType = $get('operation_type');
                                            $record = $this->getRecord();

                                            if ($operationType === null || $record->safe === null) {
                                                return [];
                                            }

                                            if ($operationType === 'transfer') {
                                                return Safe::with('currency')
                                                    ->where('currency_id', $record->safe->currency_id)
                                                    ->where('id', '!=', $record->safe_id)
                                                    ->get()
                                                    ->mapWithKeys(fn (Safe $s): array => [
                                                        $s->id => $s->name.' ('.($s->currency?->symbol ?? '').')',
                                                    ])
                                                    ->toArray();
                                            }

                                            return Safe::with('currency')
                                                ->where('currency_id', '!=', $record->safe->currency_id)
                                                ->get()
                                                ->mapWithKeys(fn (Safe $s): array => [
                                                    $s->id => $s->name.' ('.($s->currency?->symbol ?? '').')',
                                                ])
                                                ->toArray();
                                        })
                                        ->required(fn (Get $get): bool => in_array($get('operation_type'), ['exchange', 'transfer'], true))
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set) => $set('target_transaction_id', null))
                                        ->searchable(),

                                    Select::make('target_transaction_id')
                                        ->label('Hedef İşlem (API Kasası)')
                                        ->helperText('Hedef kasa API entegrasyonlu ise eşleştirilecek işlemi seçin')
                                        ->options(function (Get $get): array {
                                            $targetSafeId = $get('target_safe_id');
                                            $operationType = $get('operation_type');

                                            if ($targetSafeId === null || $operationType === null) {
                                                return [];
                                            }

                                            $targetSafe = $this->getCachedTargetSafe((int) $targetSafeId);

                                            if ($targetSafe === null || ! $targetSafe->safeGroup->is_api_integration) {
                                                return [];
                                            }

                                            $eligible = app(SafeTransactionRepository::class)
                                                ->getEligibleTransactions($this->getRecord(), $targetSafe, $operationType);

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
                                        ->visible(function (Get $get): bool {
                                            $targetSafeId = $get('target_safe_id');

                                            if ($targetSafeId === null) {
                                                return false;
                                            }

                                            $targetSafe = $this->getCachedTargetSafe((int) $targetSafeId);

                                            return $targetSafe !== null && $targetSafe->safeGroup->is_api_integration;
                                        })
                                        ->required(function (Get $get): bool {
                                            $targetSafeId = $get('target_safe_id');

                                            if ($targetSafeId === null) {
                                                return false;
                                            }

                                            $targetSafe = $this->getCachedTargetSafe((int) $targetSafeId);

                                            return $targetSafe !== null && $targetSafe->safeGroup->is_api_integration;
                                        })
                                        ->searchable(),

                                    Grid::make(2)
                                        ->visible(fn (Get $get): bool => $this->isManualExchange($get))
                                        ->schema([
                                            TextInput::make('exchange_rate')
                                                ->label('Döviz Kuru')
                                                ->numeric()
                                                ->step(0.0001)
                                                ->minValue(0)
                                                ->required(fn (Get $get): bool => $this->isManualExchange($get)),

                                            TextInput::make('target_amount')
                                                ->label('Hedef Kasaya Girecek Tutar')
                                                ->numeric()
                                                ->step(0.01)
                                                ->minValue(0)
                                                ->required(fn (Get $get): bool => $this->isManualExchange($get)),
                                        ]),
                                ]),
                        ]),
                ])
                    ->submitAction(new HtmlString(Blade::render('<x-filament::button type="submit" size="md">Kaydet</x-filament::button>'))),
            ])
            ->columns(1);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $data['_safe_name'] = $record->safe?->name ?? '—';
        $data['_type_label'] = $record->type instanceof TransactionType ? $record->type->label() : (string) $record->type;
        $data['_total_amount'] = number_format((float) $record->total_amount, 2, ',', '.').' '.($record->safe?->currency?->symbol ?? '');
        $data['_transaction_date'] = $record->transaction_date?->format('d.m.Y H:i') ?? '—';
        $data['_description'] = $record->description ?? '—';

        $operationType = $record->operation_type;
        $data['operation_type'] = $operationType instanceof OperationType ? $operationType->value : null;
        $data['transaction_category_id'] = null;
        $data['contact_id'] = null;
        $data['kurban_entry_id'] = null;
        $data['target_safe_id'] = null;
        $data['target_transaction_id'] = null;
        $data['exchange_rate'] = null;
        $data['target_amount'] = null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SafeTransaction $record */
        $operationType = $data['operation_type'];

        if ($operationType === 'normal') {
            $record->items()->update(['transaction_category_id' => (int) $data['transaction_category_id']]);
            $record->update([
                'contact_id' => $data['contact_id'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $kurbanEntryId = $data['kurban_entry_id'] ?? null;

            if ($kurbanEntryId !== null) {
                $kurbanEntry = KurbanEntry::find($kurbanEntryId);
                if ($kurbanEntry !== null) {
                    $kurbanEntry->update([
                        'is_paid' => true,
                        'paid_date' => $record->process_date,
                        'safe_transaction_id' => $record->id,
                    ]);
                }
            }

            Notification::make()
                ->success()
                ->title('Kategori Atandı')
                ->body('İşlem kategorisi başarıyla atandı.')
                ->send();

            return $record->refresh();
        }

        try {
            app(SafeTransactionService::class)->assignTransaction($record, [
                'operation_choice' => $operationType,
                'target_safe_id' => $data['target_safe_id'],
                'target_transaction_id' => $data['target_transaction_id'] ?? null,
                'exchange_rate' => $data['exchange_rate'] ?? null,
                'target_amount' => $data['target_amount'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            Notification::make()
                ->success()
                ->title('İşlem Atandı')
                ->body('İşlem başarıyla atandı ve eşleştirildi.')
                ->send();

            return $record->refresh();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Hata')
                ->body($e->getMessage())
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return SafeTransactionResource::getUrl('index');
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

    private function getRecordType(): string
    {
        $record = $this->getRecord();

        return $record->type instanceof TransactionType ? $record->type->value : (string) $record->type;
    }

    private function getCachedTargetSafe(?int $id): ?Safe
    {
        if ($id === null) {
            return null;
        }

        return $this->cachedTargetSafes[$id] ??= Safe::with('safeGroup')->find($id);
    }

    private function isManualExchange(Get $get): bool
    {
        $targetSafeId = $get('target_safe_id');

        if ($get('operation_type') !== 'exchange' || $targetSafeId === null) {
            return false;
        }

        $targetSafe = $this->getCachedTargetSafe((int) $targetSafeId);

        return $targetSafe !== null && ! $targetSafe->safeGroup->is_api_integration;
    }
}
