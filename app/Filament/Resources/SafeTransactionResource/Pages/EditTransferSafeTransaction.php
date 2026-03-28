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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTransferSafeTransaction extends EditRecord
{
    protected static string $resource = SafeTransactionResource::class;

    public ?Safe $sourceSafe = null;

    public ?Safe $targetSafe = null;

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

        if ($operationType !== 'transfer') {
            abort(403, 'Bu işlem transfer işlemi değildir.');
        }

        if ($type !== 'expense') {
            abort(403, 'Transfer düzenleme yalnızca çıkış (kaynak) kaydı üzerinden yapılabilir.');
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

        if ($this->record->targetTransaction === null) {
            abort(404, 'İlişkili transfer işlemi bulunamadı.');
        }
    }

    public function getTitle(): string
    {
        return 'Transfer İşlemini Düzenle';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['source_safe_display'] = $this->record->safe_id;
        $data['target_safe_display'] = $this->record->target_safe_id;
        $data['amount'] = (float) $this->record->total_amount;

        return $data;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Schemas\Components\Section::make('Transfer Bilgileri')
                    ->description('Kaynak ve hedef kasa bilgileri')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Schemas\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('target_safe_display')
                                    ->label('Giriş Yapılan Kasa (Hedef)')
                                    ->options(fn () => $this->targetSafe ? [$this->targetSafe->id => $this->targetSafe->name . ' (' . ($this->targetSafe->currency?->symbol ?? '') . ')'] : [])
                                    ->default(fn () => $this->targetSafe?->id)
                                    ->disabled()
                                    ->prefixIcon('heroicon-o-building-library')
                                    ->helperText(fn () => $this->targetSafe ? 'Mevcut Bakiye: ' . number_format((float) $this->targetSafe->balance, 2, ',', '.') . ' ' . ($this->targetSafe->currency?->symbol ?? 'TRY') : ''),

                                Forms\Components\Select::make('source_safe_display')
                                    ->label('Çıkış Yapılan Kasa (Kaynak)')
                                    ->options(fn () => $this->sourceSafe ? [$this->sourceSafe->id => $this->sourceSafe->name . ' (' . ($this->sourceSafe->currency?->symbol ?? '') . ')'] : [])
                                    ->default(fn () => $this->sourceSafe?->id)
                                    ->disabled()
                                    ->prefixIcon('heroicon-o-building-library')
                                    ->helperText(fn () => $this->sourceSafe ? 'Mevcut Bakiye: ' . number_format((float) $this->sourceSafe->balance, 2, ',', '.') . ' ' . ($this->sourceSafe->currency?->symbol ?? 'TRY') : ''),
                            ]),

                        Schemas\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Transfer Tutarı')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->prefix(fn () => $this->sourceSafe?->currency?->symbol ?? 'TRY')
                                    ->live(onBlur: true),
                            ]),
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
                                    ->relationship('referenceUser', 'name')
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
            'amount'             => (float) $data['amount'],
            'process_date'       => $data['process_date'],
            'description'        => $data['description'] ?? null,
            'reference_user_id'  => $data['reference_user_id'] ?? null,
        ];

        try {
            $result = app(SafeTransactionService::class)->updateTransfer($transaction, $payload);

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
}
