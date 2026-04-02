<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Enums\ContactType;
use App\Enums\TransactionType;
use App\Filament\Resources\SafeTransactionResource;
use App\Helpers\Helper;
use App\Models\KurbanEntry;
use App\Models\Safe;
use App\Models\User;
use App\Services\SafeTransactionService;
use App\Traits\HasSafeIncomeFormHelpers;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Model;

class CreateIncomeSafeTransaction extends CreateRecord
{
    use HasSafeIncomeFormHelpers;

    protected static string $resource = SafeTransactionResource::class;

    public ?int $safeId = null;

    public ?ContactType $activeContactType = null;

    public bool $activeIsKurban = false;

    protected function authorizeAccess(): void
    {
        if ($this->safeId === 0) {
            abort(403, 'Kasa ID parametresi gereklidir.');
        }

        $safe = Safe::with('safeGroup')->find($this->safeId);

        if ($safe === null) {
            abort(404, 'Kasa bulunamadı.');
        }

        if ($safe->safeGroup->is_api_integration) {
            abort(403, 'Bu kasa grubu yalnızca API üzerinden beslenebilir, panelden işlem girilemez.');
        }
    }

    public function mount(): void
    {
        $this->safeId = (int) request()->route('safe_id', 0);
        parent::mount();
    }

    public function getTitle(): string
    {
        return 'Kasa Giriş İşlemi Oluştur';
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
                                                    ->whereHas('safeGroup', fn ($q) => $q->where('is_api_integration', false))
                                                    ->get()
                                                    ->mapWithKeys(fn (Safe $s): array => [
                                                        $s->id => $s->name.' ('.($s->currency?->symbol ?? '').')',
                                                    ])
                                                    ->toArray()
                                            )
                                            ->default($this->safeId)
                                            ->disabled()
                                            ->dehydrated()
                                            ->live()
                                            ->prefixIcon('heroicon-o-building-library')
                                            ->helperText(function (Get $get): ?string {
                                                $safe = Safe::find($get('safe_id'));
                                                if ($safe === null) {
                                                    return null;
                                                }

                                                return 'Mevcut Bakiye: '.number_format((float) $safe->balance, 2, ',', '.').' '.($safe->currency?->symbol ?? 'TRY');
                                            }),

                                        Forms\Components\TextInput::make('total_amount_display')
                                            ->label('Toplam Tutar')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->prefix(fn (Get $get): string => Safe::find($get('safe_id'))?->currency?->symbol ?? 'TRY')
                                            ->placeholder('0,00')
                                            ->formatStateUsing(fn ($state) => Helper::formatShowMoney($state ?? 0)),
                                    ]),
                            ]),
                    ]),

                Schemas\Components\Section::make('Kalemler')
                    ->description('İşlem kaynakları ve tutarları')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Kalemler')
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
                                    ->default(today())
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->closeOnDateSelection(),

                                Forms\Components\Select::make('reference_user_id')
                                    ->label('İşlemi Yapan Kullanıcı')
                                    ->options(function (): array {
                                        return User::query()
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
                            ->placeholder('İşlemle ilgili detayları buraya yazabilirsiniz')
                            ->toolbarButtons([
                                'bold', 'bulletList', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo',
                            ])
                            ->columnSpanFull(),

                        Schemas\Components\Section::make('İlgili Kişi')
                            ->schema([
                                Forms\Components\Select::make('contact_id')
                                    ->label(fn (): string => $this->activeContactType?->label() ?? 'İlgili Kişi')
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

    protected function handleRecordCreation(array $data): Model
    {
        $kurbanEntryId = $data['kurban_entry_id'] ?? null;
        unset($data['kurban_entry_id']);

        $items = collect($data['items'] ?? [])->map(fn (array $item): array => [
            'transaction_category_id' => (int) $item['transaction_category_id'],
            'amount' => (float) ($item['amount'] ?? 0),
        ])->toArray();
        $total = collect($items)->sum(fn ($i): float => $i['amount']);

        $payload = [
            'safe_id' => $this->safeId,
            'type' => TransactionType::INCOME->value,
            'total_amount' => $total,
            'process_date' => $data['process_date'],
            'description' => $data['description'] ?? null,
            'reference_user_id' => $data['reference_user_id'] ?? null,
            'contact_id' => $data['contact_id'] ?? null,
            'items' => $items,
        ];

        try {
            $transaction = app(SafeTransactionService::class)->create($payload);

            // Eğer kurban entry seçildiyse, ödendi olarak işaretle
            if ($kurbanEntryId !== null) {
                $kurbanEntry = KurbanEntry::find($kurbanEntryId);
                if ($kurbanEntry !== null) {
                    $kurbanEntry->update([
                        'is_paid' => true,
                        'paid_date' => $data['process_date'],
                        'safe_transaction_id' => $transaction->id,
                    ]);
                }
            }

            return $transaction;
        } catch (\RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('İşlem Hatası')
                ->body($e->getMessage())
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
