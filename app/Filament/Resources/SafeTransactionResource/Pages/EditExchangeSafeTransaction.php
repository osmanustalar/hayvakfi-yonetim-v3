<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Enums\OperationType;
use App\Enums\TransactionType;
use App\Filament\Resources\SafeTransactionResource;
use App\Models\Safe;
use App\Models\SafeTransaction;
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

class EditExchangeSafeTransaction extends EditRecord
{
    protected static string $resource = SafeTransactionResource::class;

    public Safe $sourceSafe;

    public Safe $targetSafe;

    public SafeTransaction $targetTransaction;

    protected function authorizeAccess(): void
    {
        /** @var SafeTransaction $record */
        $record = $this->getRecord();

        $operationType = $record->operation_type instanceof OperationType
            ? $record->operation_type->value
            : (string) $record->operation_type;

        $type = $record->type instanceof TransactionType
            ? $record->type->value
            : (string) $record->type;

        if ($operationType !== 'exchange') {
            abort(403, 'Bu işlem döviz işlemi değildir.');
        }

        if ($type !== 'expense') {
            abort(403, 'Döviz düzenleme yalnızca çıkış (kaynak) kaydı üzerinden yapılabilir.');
        }
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        /** @var Safe $sourceSafe */
        $sourceSafe = $this->record->safe;
        $this->sourceSafe = $sourceSafe;

        /** @var Safe $targetSafe */
        $targetSafe = Safe::withoutGlobalScopes()->findOrFail($this->record->target_safe_id);
        $this->targetSafe = $targetSafe;

        /** @var SafeTransaction $targetTransaction */
        $targetTransaction = SafeTransaction::withoutGlobalScopes()->findOrFail($this->record->target_transaction_id);
        $this->targetTransaction = $targetTransaction;
    }

    public function getTitle(): string
    {
        return 'Döviz İşlemini Düzenle';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['source_amount'] = $this->record->total_amount;
        $data['target_amount'] = $this->targetTransaction->total_amount;

        return $data;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Schemas\Components\Section::make('Döviz İşlemi Bilgileri')
                    ->description('Kaynak ve hedef kasa bilgileri')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Schemas\Components\Grid::make(2)
                            ->schema([
                                // Hedef kasa gösterimi (readonly)
                                Forms\Components\Select::make('target_safe_display')
                                    ->label('Giriş Yapılan Kasa (Hedef)')
                                    ->options([$this->targetSafe->id => $this->targetSafe->name . ' (' . ($this->targetSafe->currency?->symbol ?? '') . ')'])
                                    ->default($this->targetSafe->id)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-building-library')
                                    ->helperText('Mevcut Bakiye: ' . number_format((float) $this->targetSafe->balance, 2, ',', '.') . ' ' . ($this->targetSafe->currency?->symbol ?? 'TRY')),

                                // Kaynak kasa gösterimi (readonly)
                                Forms\Components\Select::make('source_safe_display')
                                    ->label('Çıkış Yapılan Kasa (Kaynak)')
                                    ->options([$this->sourceSafe->id => $this->sourceSafe->name . ' (' . ($this->sourceSafe->currency?->symbol ?? '') . ')'])
                                    ->default($this->sourceSafe->id)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-building-library')
                                    ->helperText('Mevcut Bakiye: ' . number_format((float) $this->sourceSafe->balance, 2, ',', '.') . ' ' . ($this->sourceSafe->currency?->symbol ?? 'TRY')),
                            ]),

                        Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('target_amount')
                                    ->label('Giriş Tutarı (' . ($this->targetSafe->currency?->symbol ?? '—') . ')')
                                    ->required()
                                    ->mask(RawJs::make('$money($input, \',\')'))
                                    ->prefix($this->targetSafe->currency?->symbol ?? '—')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                        $sourceAmount = (float) str_replace(['.', ','], ['', '.'], $get('source_amount') ?? '0');
                                        $targetAmount = (float) str_replace(['.', ','], ['', '.'], $state ?? '0');
                                        self::recalculateRate($sourceAmount, $targetAmount, $set);
                                    }),

                                Forms\Components\TextInput::make('source_amount')
                                    ->label('Çıkış Tutarı (' . ($this->sourceSafe->currency?->symbol ?? 'TRY') . ')')
                                    ->required()
                                    ->mask(RawJs::make('$money($input, \',\')'))
                                    ->prefix($this->sourceSafe->currency?->symbol ?? 'TRY')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                        $sourceAmount = (float) str_replace(['.', ','], ['', '.'], $state ?? '0');
                                        $targetAmount = (float) str_replace(['.', ','], ['', '.'], $get('target_amount') ?? '0');
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
                    ]),
            ])
            ->columns(1);
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var SafeTransaction $transaction */
        $transaction = $record;

        $payload = [
            'source_amount'      => (float) str_replace(['.', ','], ['', '.'], $data['source_amount']),
            'target_amount'      => (float) str_replace(['.', ','], ['', '.'], $data['target_amount']),
            'item_rate'          => (float) ($data['item_rate'] ?? 0),
            'process_date'       => $data['process_date'],
            'description'        => $data['description'] ?? null,
            'reference_user_id'  => $data['reference_user_id'] ?? null,
        ];

        try {
            $result = app(SafeTransactionService::class)->updateExchange($transaction, $payload);

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

    private static function recalculateRate(float $sourceAmount, float $targetAmount, Set $set): void
    {
        if ($targetAmount > 0 && $sourceAmount > 0) {
            $rate1 = $sourceAmount / $targetAmount;
            $rate2 = $targetAmount / $sourceAmount;
            $set('item_rate', number_format(max($rate1, $rate2), 4, '.', ''));
        }
    }
}
