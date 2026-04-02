<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Enums\ContactType;
use App\Enums\TransactionType;
use App\Filament\Resources\SafeTransactionResource;
use App\Helpers\Helper;
use App\Models\KurbanEntry;
use App\Models\Safe;
use App\Models\SafeTransaction;
use App\Services\SafeTransactionService;
use App\Traits\HasSafeIncomeFormHelpers;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\RawJs;

class EditIncomeSafeTransaction extends EditRecord
{
    use HasSafeIncomeFormHelpers;

    protected static string $resource = SafeTransactionResource::class;

    public ?ContactType $activeContactType = null;

    public bool $activeIsKurban = false;

    protected function authorizeAccess(): void
    {
        /** @var SafeTransaction $record */
        $record = $this->getRecord();

        $type = $record->type instanceof TransactionType
            ? $record->type->value
            : (string) $record->type;

        if ($type !== 'income' || $record->operation_type !== null) {
            abort(403, 'Bu sayfa yalnızca gelir işlemleri için kullanılabilir.');
        }
    }


    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Mevcut kategoriden contact_type ve is_sacrifice_type al
        foreach ($this->record->items as $item) {
            if ($item->transactionCategory?->contact_type !== null) {
                $this->activeContactType = $item->transactionCategory->contact_type;
                $this->activeIsKurban = (bool) ($item->transactionCategory->is_sacrifice_type ?? false);
                break;
            }
        }
    }

    public function getTitle(): string
    {
        return 'Kasa Giriş İşlemini Düzenle';
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Schemas\Components\Section::make('Kasa Giriş Bilgileri')
                    ->description('Giriş yapılan kasa bilgileri')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Schemas\Components\Grid::make(2)
                            ->schema([
                                // SOL: Giriş Yapılan Kasa
                                Schemas\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Select::make('safe_id')
                                            ->label('Giriş Yapılan Kasa')
                                            ->required()
                                            ->options(
                                                Safe::query()
                                                    ->where('is_active', true)
                                                    ->with('currency')
                                                    ->get()
                                                    ->mapWithKeys(fn (Safe $s): array => [
                                                        $s->id => $s->name . ' (' . ($s->currency?->symbol ?? '') . ')',
                                                    ])
                                                    ->toArray()
                                            )
                                            ->disabled()
                                            ->dehydrated()
                                            ->live()
                                            ->prefixIcon('heroicon-o-building-library')
                                            ->helperText(function (Get $get): ?string {
                                                $safe = Safe::find($get('safe_id'));
                                                if ($safe === null) {
                                                    return null;
                                                }

                                                return 'Mevcut Bakiye: ' . number_format((float) $safe->balance, 2, ',', '.') . ' ' . ($safe->currency?->symbol ?? 'TRY');
                                            }),

                                        Forms\Components\TextInput::make('total_amount_display')
                                            ->label('Toplam Tutar')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->prefix(fn (Get $get): string => Safe::find($get('safe_id'))?->currency?->symbol ?? 'TRY')
                                            ->helperText(fn (): ?string => $this->record->integration_id !== null
                                                ? 'API işlemi: Toplam tutar ' . Helper::formatShowMoney($this->record->total_amount) . ' olarak sabit kalmalıdır. Kalemler arasında dağıtabilirsiniz.'
                                                : null
                                            ),
                                    ]),
                            ]),
                    ]),

                Schemas\Components\Section::make('Kalemler')
                    ->description('İşlem kaynakları ve tutarları')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Kalemler')
                            ->relationship('items')
                            ->schema([
                                Schemas\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('amount')
                                            ->required()
                                            ->label('Tutar')
                                            ->mask(RawJs::make(<<<'JS'
                                            $money($input, ',')
                                            JS))
                                            ->live(onBlur: true)
                                            ->prefix(fn (Get $get): string => Safe::find($get('../../safe_id'))?->currency?->symbol ?? 'TRY')
                                            ->placeholder('0,00')
                                            ->formatStateUsing(fn ($state) => Helper::formatShowMoney($state ?? 0))
                                            ->dehydrateStateUsing(fn (?string $state): ?float => $state !== null ? (float) str_replace(',', '.', $state) : null)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                                $items = $get('../../items') ?? [];
                                                $total = 0;

                                                foreach ($items as $item) {
                                                    if (isset($item['amount'])) {
                                                        $total += Helper::formatSaveMoney($item['amount']);
                                                    }
                                                }

                                                $formattedTotal = Helper::formatShowMoney($total);
                                                $set('../../total_amount_display', $formattedTotal);
                                            }),

                                        Forms\Components\Select::make('transaction_category_id')
                                            ->label('Kategori')
                                            ->required()
                                            ->options(function (): array {
                                                return self::buildCategoryOptions('income');
                                            })
                                            ->live()
                                            ->afterStateUpdated(fn (?int $state, Set $set) => $this->handleCategoryStateUpdated($state, $set))
                                            ->searchable()
                                            ->prefixIcon('heroicon-o-tag'),
                                    ]),
                            ])
                            ->addActionLabel('Kalem Ekle')
                            ->deletable(true)
                            ->reorderable(false)
                            ->minItems(1)
                            ->required(),
                    ]),

                Schemas\Components\Section::make('Kurban Kaydı')
                    ->description('Kurban kategorisi seçildiyse listeden bir kayıt seçebilirsiniz')
                    ->icon('heroicon-o-user-group')
                    ->visible(fn (): bool => $this->activeIsKurban)
                    ->schema([
                        Forms\Components\Select::make('kurban_entry_id')
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

                Schemas\Components\Section::make('İşlem Detayları')
                    ->description('Kasa işlemi detayları')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('process_date')
                                    ->label('İşlem Tarihi')
                                    ->required()
                                    ->disabled(fn (): bool => $this->record->integration_id !== null)
                                    ->helperText(fn (): ?string => $this->record->integration_id !== null ? 'API işlemlerinin tarihi değiştirilemez' : null)
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->closeOnDateSelection(),

                                Forms\Components\Select::make('reference_user_id')
                                    ->label('İşlemi Yapan Kullanıcı')
                                    ->options(function (): array {
                                        return \App\Models\User::query()
                                            ->whereHas('companies', fn ($q) => $q->where('company_id', session('active_company_id')))
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn ($u) => [$u->id => $u->name])
                                            ->toArray();
                                    })
                                    ->prefixIcon('heroicon-o-user')
                                    ->searchable(),
                            ]),

                        Forms\Components\RichEditor::make('description')
                            ->label('Açıklama')
                            ->toolbarButtons([
                                'bold', 'bulletList', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo',
                            ])
                            ->columnSpanFull(),

                        Schemas\Components\Section::make('İlgili Kişi')
                            ->schema([
                                Forms\Components\Select::make('contact_id')
                                    ->label(fn (): string => $this->activeContactType?->label() ?? 'İlgili Kişi')
                                    ->required(fn (): bool => $this->activeContactType !== null)
                                    ->options(function (): array {
                                        if ($this->activeContactType === null) {
                                            return [];
                                        }

                                        return $this->buildContactOptions($this->activeContactType, $this->activeIsKurban);
                                    })
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-user-group')
                                    ->columnSpanFull(),
                            ])
                            ->visible(fn (): bool => $this->activeContactType !== null),
                    ]),
            ])
            ->columns(1);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['total_amount_display'] = Helper::formatShowMoney($this->record->total_amount ?? 0);
        return $data;
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var SafeTransaction $transaction */
        $transaction = $record;

        $kurbanEntryId = $data['kurban_entry_id'] ?? null;
        unset($data['kurban_entry_id']);

        // Eğer form'dan items gelmemişse (hiçbir değişiklik yapılmadıysa), mevcut items'i kullan
        $formItems = $data['items'] ?? [];
        if (empty($formItems)) {
            $items = $transaction->items->map(fn ($item): array => [
                'transaction_category_id' => $item->transaction_category_id,
                'amount'                  => (float) $item->amount,
            ])->toArray();
        } else {
            $items = collect($formItems)->map(fn (array $item): array => [
                'transaction_category_id' => (int) $item['transaction_category_id'],
                'amount'                  => (float) ($item['amount'] ?? 0),
            ])->toArray();
        }

        $newTotal = collect($items)->sum(fn ($i): float => $i['amount']);

        $payload = [
            'type'               => TransactionType::INCOME->value,
            'total_amount'       => $newTotal,
            'process_date'       => $transaction->integration_id !== null ? $transaction->process_date : $data['process_date'],
            'description'        => $data['description'] ?? null,
            'reference_user_id'  => $data['reference_user_id'] ?? null,
            'contact_id'         => $data['contact_id'] ?? null,
            'items'              => $items,
        ];

        try {
            // API kayıtlarında toplam tutar değiştirilemesin
            if ($transaction->integration_id !== null) {
                $originalTotal = (float) $transaction->total_amount;
                if (abs($newTotal - $originalTotal) > 0.001) {
                    throw new \RuntimeException(
                        "API\'den geri verilen işlemlerde toplam tutar değiştirilemez. " .
                        "Orijinal tutar: " . number_format($originalTotal, 2, ',', '.') .
                        ", Yeni tutar: " . number_format($newTotal, 2, ',', '.')
                    );
                }
            }

            // API kayıtlarında işlem tarihini değiştirme
            if ($transaction->integration_id !== null && (string) $data['process_date'] !== (string) $transaction->process_date) {
                throw new \RuntimeException('API\'den geri verilen işlemlerin tarihi değiştirilemez.');
            }

            $updatedTransaction = app(SafeTransactionService::class)->update($transaction, $payload);

            // Eğer kurban entry seçildiyse, ödendi olarak işaretle
            if ($kurbanEntryId !== null) {
                $kurbanEntry = KurbanEntry::find($kurbanEntryId);
                if ($kurbanEntry !== null) {
                    $kurbanEntry->update([
                        'is_paid'              => true,
                        'paid_date'            => $payload['process_date'],
                        'safe_transaction_id'  => $updatedTransaction->id,
                    ]);
                }
            }

            return $updatedTransaction;
        } catch (\RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('İşlem Hatası')
                ->body($e->getMessage())
                ->send();

            $this->halt();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Sil')
                ->visible(fn (SafeTransaction $record): bool => $record->integration_id === null)
                ->tooltip(fn (SafeTransaction $record): ?string => $record->integration_id !== null
                    ? 'API\'den geri verilen işlemler silinemez'
                    : null
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
