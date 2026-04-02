<?php

declare(strict_types=1);

namespace App\Filament\Resources\SafeTransactionResource\Pages;

use App\Filament\Resources\SafeTransactionResource;
use App\Models\Safe;
use App\Models\User;
use App\Services\SafeTransactionService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateTransferSafeTransaction extends CreateRecord
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
        return 'Transfer Çıkışı Oluştur';
    }

    public function form(Schema $form): Schema
    {
        $sourceSafe = Safe::find($this->safeId);

        return $form
            ->schema([
                Schemas\Components\Section::make('Transfer Bilgileri')
                    ->description('Giriş ve çıkış yapan kasa bilgileri')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Schemas\Components\Grid::make(2)
                            ->schema([
                                // SOL: Giriş Yapılan Kasa (Hedef)
                                Schemas\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Select::make('target_safe_id')
                                            ->label('Giriş Yapılan Kasa (Hedef)')
                                            ->required()
                                            ->options(function () use ($sourceSafe): array {
                                                if ($sourceSafe === null) {
                                                    return [];
                                                }

                                                return Safe::query()
                                                    ->where('is_active', true)
                                                    ->where('id', '!=', $sourceSafe->id)
                                                    ->where('currency_id', $sourceSafe->currency_id)
                                                    ->whereHas('safeGroup', fn ($q) => $q->where('is_api_integration', false))
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

                        Schemas\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Transfer Tutarı')
                                    ->required()
                                    ->step(0.01)
                                    ->inputMode('decimal')
                                    ->prefix(fn (): string => Safe::find($this->safeId)?->currency?->symbol ?? 'TRY')
                                    ->formatStateUsing(fn (?float $state): string => $state !== null ? number_format($state, 2, ',', '') : '')
                                    ->dehydrateStateUsing(fn (?string $state): ?float => $state !== null ? (float) str_replace(',', '.', $state) : null)
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
                            ->placeholder('Transfer ile ilgili detayları buraya yazabilirsiniz')
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
        // Aynı para birimi kontrolü
        $sourceSafe = Safe::find($this->safeId);
        $targetSafe = Safe::find($data['target_safe_id'] ?? null);

        if ($sourceSafe === null || $targetSafe === null) {
            Notification::make()->danger()->title('Hata')->body('Kasa bulunamadı.')->send();
            $this->halt();
        }

        if ($sourceSafe->currency_id !== $targetSafe->currency_id) {
            Notification::make()
                ->danger()
                ->title('Para Birimi Hatası')
                ->body('Transfer işlemi için aynı para birimine sahip kasa seçmelisiniz.')
                ->send();
            $this->halt();
        }

        $payload = [
            'source_safe_id' => $this->safeId,
            'target_safe_id' => $data['target_safe_id'],
            'amount' => (float) $data['amount'],
            'process_date' => $data['process_date'],
            'description' => $data['description'] ?? null,
            'reference_user_id' => $data['reference_user_id'] ?? null,
        ];

        try {
            $result = app(SafeTransactionService::class)->createTransfer($payload);

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
}
