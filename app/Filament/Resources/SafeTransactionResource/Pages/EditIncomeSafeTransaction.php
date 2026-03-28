<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Enums\ContactType;
use App\Enums\TransactionType;
use App\Filament\Resources\SafeTransactionResource;
use App\Models\Contact;
use App\Models\Safe;
use App\Models\SafeTransaction;
use App\Models\SafeTransactionCategory;
use App\Services\SafeTransactionService;
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
    protected static string $resource = SafeTransactionResource::class;

    public ?ContactType $activeContactType = null;

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

        if ($record->safe?->safeGroup?->is_api_integration === true) {
            abort(403, 'Bu kasa grubu yalnızca API üzerinden güncellenebilir.');
        }
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Mevcut kategoriden contact_type al
        foreach ($this->record->items as $item) {
            if ($item->transactionCategory?->contact_type !== null) {
                $this->activeContactType = $item->transactionCategory->contact_type;
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
                                                    ->whereHas('safeGroup', fn($q) => $q->where('is_api_integration', false))
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
                                            ->formatStateUsing(function (Get $get): string {
                                                $items = $get('items') ?? [];
                                                if (!is_array($items)) {
                                                    $items = $items?->toArray() ?? [];
                                                }
                                                $total = collect($items)->sum(fn ($i): float => (float) str_replace(['.', ','], ['', '.'], $i['amount'] ?? '0'));
                                                return number_format($total, 2, ',', '.');
                                            }),
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
                            ->live(onBlur: true)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Tutar')
                                    ->required()
                                    ->mask(RawJs::make('$money($input, \',\')'))
                                    ->live(onBlur: true)
                                    ->prefix(fn (Get $get): string => Safe::find($get('../../safe_id'))?->currency?->symbol ?? 'TRY')
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        $items = $get('../items') ?? [];
                                        if (!is_array($items)) {
                                            $items = $items?->toArray() ?? [];
                                        }
                                        $total = collect($items)->sum(fn ($i): float => (float) str_replace(['.', ','], ['', '.'], $i['amount'] ?? '0'));
                                        $set('../../total_amount_display', number_format($total, 2, ',', '.'));
                                    }),

                                Forms\Components\Select::make('transaction_category_id')
                                    ->label('Kategori')
                                    ->required()
                                    ->options(function (): array {
                                        return self::buildCategoryOptions('income');
                                    })
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (?int $state, Set $set): void {
                                        if ($state === null) {
                                            $this->activeContactType = null;

                                            return;
                                        }

                                        $category = SafeTransactionCategory::find($state);

                                        if ($category === null) {
                                            return;
                                        }

                                        if ($category->children()->exists()) {
                                            $set('transaction_category_id', null);
                                            Notification::make()
                                                ->danger()
                                                ->title('Kategori Seçimi Engellendi')
                                                ->body('Alt kategorisi olan ana kategori seçilemez. Lütfen bir alt kategori seçin.')
                                                ->send();

                                            return;
                                        }

                                        $this->activeContactType = $category->contact_type;
                                    })
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-tag'),
                            ])
                            ->addActionLabel('Kalem Ekle')
                            ->deletable(true)
                            ->reorderable(false)
                            ->columns(2)
                            ->minItems(1)
                            ->required(),
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
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->closeOnDateSelection(),

                                Forms\Components\Select::make('reference_user_id')
                                    ->label('İşlemi Yapan Kullanıcı')
                                    ->required()
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

                                        return $this->buildContactOptions($this->activeContactType);
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

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var SafeTransaction $transaction */
        $transaction = $record;

        $items = collect($data['items'] ?? [])->map(fn (array $item): array => [
            'transaction_category_id' => (int) $item['transaction_category_id'],
            'amount'                  => (float) $item['amount'],
        ])->toArray();

        $total = collect($items)->sum(fn ($i): float => $i['amount']);

        $payload = [
            'type'               => TransactionType::INCOME->value,
            'total_amount'       => $total,
            'amount'             => $total,
            'process_date'       => $data['process_date'],
            'description'        => $data['description'] ?? null,
            'reference_user_id'  => $data['reference_user_id'] ?? null,
            'contact_id'         => $data['contact_id'] ?? null,
            'items'              => $items,
        ];

        try {
            return app(SafeTransactionService::class)->update($transaction, $payload);
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
            DeleteAction::make()->label('Sil'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @return array<int|string, string>
     */
    private static function buildCategoryOptions(string $type): array
    {
        $parents = SafeTransactionCategory::query()
            ->forActiveCompany()
            ->where('type', $type)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $options = [];

        foreach ($parents as $parent) {
            $children = SafeTransactionCategory::query()
                ->forActiveCompany()
                ->where('type', $type)
                ->where('is_active', true)
                ->where('parent_id', $parent->id)
                ->orderBy('sort_order')
                ->get();

            if ($children->isEmpty()) {
                $options[$parent->id] = $parent->name;
            } else {
                $options[$parent->id] = $parent->name . ' (Seçilemez)';

                foreach ($children as $child) {
                    $options[$child->id] = '⤷ ' . $parent->name . ' → ' . $child->name;
                }
            }
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private function buildContactOptions(ContactType $contactType): array
    {
        $column = match ($contactType) {
            ContactType::DONOR         => 'is_donor',
            ContactType::AID_RECIPIENT => 'is_aid_recipient',
            ContactType::STUDENT       => 'is_student',
        };

        return Contact::query()
            ->where($column, true)
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn (Contact $c): array => [
                $c->id => $c->first_name . ' ' . $c->last_name . ($c->phone ? ' (' . $c->phone . ')' : ''),
            ])
            ->toArray();
    }
}
