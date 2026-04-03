<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Enums\OperationType;
use App\Enums\TransactionType;
use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\SafeTransactionResource;
use App\Models\Safe;
use App\Models\SafeTransaction;
use App\Models\User;
use App\Repositories\SafeTransactionItemRepository;
use App\Repositories\SafeTransactionRepository;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateExchangeSafeTransaction extends BaseCreateRecord
{
    protected static string $resource = SafeTransactionResource::class;

    public ?int $safeId = null;

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
        return 'Döviz Çıkışı Oluştur';
    }

    public function form(Schema $form): Schema
    {
        $sourceSafe = Safe::find($this->safeId);

        return $form
            ->schema([
                Schemas\Components\Section::make('Döviz Çıkış Bilgileri')
                    ->description('Giriş ve çıkış yapan kasa bilgileri')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Schemas\Components\Grid::make(2)
                            ->schema([
                                // SOL: Giriş Yapılan Kasa (Hedef — farklı para birimi)
                                Schemas\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Select::make('target_safe_id')
                                            ->label('Giriş Yapılan Kasa (Hedef — farklı para birimi)')
                                            ->required()
                                            ->options(function () use ($sourceSafe): array {
                                                if ($sourceSafe === null) {
                                                    return [];
                                                }

                                                return Safe::query()
                                                    ->where('is_active', true)
                                                    ->where('id', '!=', $sourceSafe->id)
                                                    ->where('currency_id', '!=', $sourceSafe->currency_id)
                                                    ->where('safe_group_id', $sourceSafe->safe_group_id)
                                                    ->get()
                                                    ->mapWithKeys(fn (Safe $s): array => [
                                                        $s->id => $s->name.' ('.($s->currency?->symbol ?? '').')',
                                                    ])
                                                    ->toArray();
                                            })
                                            ->live()
                                            ->prefixIcon('heroicon-o-building-library')
                                            ->helperText(function (Get $get): ?string {
                                                $safe = Safe::find($get('target_safe_id'));
                                                if ($safe === null) {
                                                    return null;
                                                }

                                                return 'Mevcut Bakiye: '.number_format((float) $safe->balance, 2, ',', '.').' '.($safe->currency?->symbol ?? 'TRY');
                                            }),
                                    ]),

                                // SAĞ: Çıkış Yapılan Kasa (Kaynak)
                                Schemas\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Select::make('source_safe_id')
                                            ->label('Çıkış Yapılan Kasa (Kaynak)')
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
                                            ->prefixIcon('heroicon-o-building-library')
                                            ->helperText(function () use ($sourceSafe): ?string {
                                                if ($sourceSafe === null) {
                                                    return null;
                                                }

                                                return 'Mevcut Bakiye: '.number_format((float) $sourceSafe->balance, 2, ',', '.').' '.($sourceSafe->currency?->symbol ?? 'TRY');
                                            }),
                                    ]),
                            ]),

                        Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('target_amount')
                                    ->label('Giriş Tutarı (Hedef Para Birimi)')
                                    ->required()
                                    ->step(0.01)
                                    ->inputMode('decimal')
                                    ->prefix(fn (Get $get): string => Safe::find($get('target_safe_id'))?->currency?->symbol ?? '—')
                                    ->formatStateUsing(fn (?float $state): string => $state !== null ? number_format($state, 2, ',', '') : '')
                                    ->dehydrateStateUsing(fn (?string $state): ?float => $state !== null ? (float) str_replace(',', '.', $state) : null)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                        $sourceAmount = (float) str_replace(',', '.', $get('source_amount') ?? '0');
                                        $targetAmount = (float) str_replace(',', '.', $state ?? '0');
                                        self::recalculateRate($sourceAmount, $targetAmount, $set);
                                    }),

                                Forms\Components\TextInput::make('source_amount')
                                    ->label('Çıkış Tutarı (Kaynak Para Birimi)')
                                    ->required()
                                    ->step(0.01)
                                    ->inputMode('decimal')
                                    ->prefix(fn (): string => Safe::find($this->safeId)?->currency?->symbol ?? 'TRY')
                                    ->formatStateUsing(fn (?float $state): string => $state !== null ? number_format($state, 2, ',', '') : '')
                                    ->dehydrateStateUsing(fn (?string $state): ?float => $state !== null ? (float) str_replace(',', '.', $state) : null)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                        $sourceAmount = (float) str_replace(',', '.', $state ?? '0');
                                        $targetAmount = (float) str_replace(',', '.', $get('target_amount') ?? '0');
                                        self::recalculateRate($sourceAmount, $targetAmount, $set);
                                    }),
                            ]),

                        Forms\Components\TextInput::make('item_rate')
                            ->label('Döviz Kuru (otomatik hesaplanır)')
                            ->readOnly()
                            ->numeric()
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
                                    ->required()
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
                            ->placeholder('Döviz işlemi ile ilgili detayları buraya yazabilirsiniz')
                            ->toolbarButtons([
                                'bold', 'bulletList', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo',
                            ])
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $sourceSafe = Safe::find($this->safeId);
        $targetSafe = Safe::find($data['target_safe_id'] ?? null);

        if ($sourceSafe === null || $targetSafe === null) {
            Notification::make()->danger()->title('Hata')->body('Kasa bulunamadı.')->send();
            $this->halt();
        }

        if ($sourceSafe->currency_id === $targetSafe->currency_id) {
            Notification::make()
                ->danger()
                ->title('Para Birimi Hatası')
                ->body('Döviz işlemi için farklı para birimine sahip kasa seçmelisiniz.')
                ->send();
            $this->halt();
        }

        $sourceAmount = (float) $data['source_amount'];
        $targetAmount = (float) $data['target_amount'];
        $itemRate = (float) ($data['item_rate'] ?? 0);
        $companyId = (int) session('active_company_id');
        $createdById = auth()->id();

        try {
            /** @var array{source: SafeTransaction, target: SafeTransaction} $result */
            $result = DB::transaction(function () use (
                $sourceSafe,
                $targetSafe,
                $sourceAmount,
                $targetAmount,
                $itemRate,
                $data,
                $companyId,
                $createdById,
            ): array {
                /** @var SafeTransactionRepository $txRepo */
                $txRepo = app(SafeTransactionRepository::class);

                /** @var SafeTransactionItemRepository $itemRepo */
                $itemRepo = app(SafeTransactionItemRepository::class);

                // Kaynak: gider (çıkış kasa)
                /** @var SafeTransaction */
                $sourceTransaction = $txRepo->create([
                    'company_id' => $companyId,
                    'safe_id' => $sourceSafe->id,
                    'type' => TransactionType::EXPENSE->value,
                    'operation_type' => OperationType::EXCHANGE->value,
                    'total_amount' => $sourceAmount,
                    'amount' => $sourceAmount,
                    'currency_id' => $sourceSafe->currency_id,
                    'item_rate' => $itemRate,
                    'target_safe_id' => $targetSafe->id,
                    'process_date' => $data['process_date'],
                    'description' => $data['description'] ?? null,
                    'reference_user_id' => $data['reference_user_id'] ?? null,
                    'created_user_id' => $createdById,
                    'balance_after_created' => 0,
                ]);

                $sourceSafe->decrement('balance', $sourceAmount);
                $sourceTransaction->update(['balance_after_created' => $sourceSafe->fresh()->balance]);

                // Hedef: gelir (giriş kasa)
                /** @var SafeTransaction */
                $targetTransaction = $txRepo->create([
                    'company_id' => $companyId,
                    'safe_id' => $targetSafe->id,
                    'type' => TransactionType::INCOME->value,
                    'operation_type' => OperationType::EXCHANGE->value,
                    'total_amount' => $targetAmount,
                    'amount' => $targetAmount,
                    'currency_id' => $targetSafe->currency_id,
                    'item_rate' => $itemRate,
                    'target_safe_id' => $sourceSafe->id,
                    'target_transaction_id' => $sourceTransaction->id,
                    'process_date' => $data['process_date'],
                    'description' => $data['description'] ?? null,
                    'reference_user_id' => $data['reference_user_id'] ?? null,
                    'created_user_id' => $createdById,
                    'balance_after_created' => 0,
                ]);

                $targetSafe->increment('balance', $targetAmount);
                $targetTransaction->update(['balance_after_created' => $targetSafe->fresh()->balance]);

                // Çapraz referans
                $sourceTransaction->update(['target_transaction_id' => $targetTransaction->id]);

                // Kategori item'ları (ID: 2 = Döviz İşlemleri)
                $itemRepo->createMany($sourceTransaction->id, $companyId, [
                    ['transaction_category_id' => 2, 'amount' => $sourceAmount],
                ]);
                $itemRepo->createMany($targetTransaction->id, $companyId, [
                    ['transaction_category_id' => 2, 'amount' => $targetAmount],
                ]);

                return ['source' => $sourceTransaction, 'target' => $targetTransaction];
            });

            return $result['source'];
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

    private static function recalculateRate(float $sourceAmount, float $targetAmount, Set $set): void
    {
        if ($targetAmount > 0 && $sourceAmount > 0) {
            $rate1 = $sourceAmount / $targetAmount;
            $rate2 = $targetAmount / $sourceAmount;
            $set('item_rate', number_format(max($rate1, $rate2), 4, '.', ''));
        }
    }
}
