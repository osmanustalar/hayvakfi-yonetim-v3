<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SafeResource\Pages;
use App\Filament\Resources\SafeTransactionResource;
use App\Models\Currency;
use App\Models\Safe;
use App\Models\SafeGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SafeResource extends Resource
{
    protected static ?string $model = Safe::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kasa';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Kasalar';

    protected static ?string $modelLabel = 'Kasa';

    protected static ?string $pluralModelLabel = 'Kasalar';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery()
            ->with('safeGroup')
            ->addSelect(DB::raw(
                '(SELECT MAX(created_at) FROM safe_transactions WHERE safe_id = safes.id) as latest_transaction_date'
            ));

        // super_admin tüm kasaları görebilir, diğerleri sadece kendilerine atanmış kasaları
        if (! auth()->user()?->hasRole('super_admin')) {
            $query->whereHas('users', fn ($q) => $q->where('users.id', auth()->id()));
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Kasa Bilgileri')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('safe_group_id')
                                    ->label('Kasa Grubu')
                                    ->required()
                                    ->options(SafeGroup::query()->pluck('name', 'id'))
                                    ->searchable(),

                                TextInput::make('name')
                                    ->label('Kasa Adı')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('currency_id')
                                    ->label('Para Birimi')
                                    ->required()
                                    ->options(Currency::where('is_active', true)->pluck('name', 'id')),

                                TextInput::make('iban')
                                    ->label('IBAN')
                                    ->nullable()
                                    ->maxLength(34),

                                TextInput::make('sort_order')
                                    ->label('Sıra')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Erişim İzinleri')
                    ->description('Bu kasaya erişebilen kullanıcılar')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Select::make('users')
                            ->label('Erişim Verilecek Kullanıcılar')
                            ->multiple()
                            ->relationship(
                                'users',
                                'name',
                                fn ($query) => $query
                                    ->whereHas('companies', fn ($q) => $q->where('company_id', session('active_company_id')))
                                    ->orderBy('name')
                            )
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-users')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kasa Adı')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('index') . '?safe_id=' . $record->id),

                TextColumn::make('balance')
                    ->label('Bakiye')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('index') . '?safe_id=' . $record->id),

                TextColumn::make('currency.symbol')
                    ->label('Döviz')
                    ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('index') . '?safe_id=' . $record->id),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('index') . '?safe_id=' . $record->id),

                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('latest_transaction_date')
                    ->label('Son Hareket')
                    ->formatStateUsing(fn (?string $state): string =>
                        $state ? \Carbon\Carbon::parse($state)->format('d.m.Y H:i') : '—'
                    )
                    ->placeholder('—')
                    ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('index') . '?safe_id=' . $record->id),
            ])
            ->paginationPageOptions([20, 50, 100])
            ->defaultPaginationPageOption(20)
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('safe_group_id')
                    ->label('Kasa Grubu')
                    ->options(SafeGroup::query()->pluck('name', 'id')),

                TernaryFilter::make('is_active')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),
            ])
            ->actions([
                // Hızlı işlem butonları — yalnızca API entegrasyonlu olmayan kasa gruplarında
                Action::make('income')
                    ->label('Giriş')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Safe $record): bool => ! $record->safeGroup->is_api_integration && $record->is_active)
                    ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('create-income', [
                        'safe_id' => $record->id,
                    ])),

                Action::make('expense')
                    ->label('Çıkış')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('danger')
                    ->visible(fn (Safe $record): bool => ! $record->safeGroup->is_api_integration && $record->is_active)
                    ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('create-expense', [
                        'safe_id' => $record->id,
                    ])),

                Action::make('transactions')
                    ->label('Hareketler')
                    ->icon('heroicon-o-list-bullet')
                    ->color('gray')
                    ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('index') . '?safe_id=' . $record->id),

                ActionGroup::make([
                    Action::make('transfer')
                        ->label('Transfer Çıkışı')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->visible(fn (Safe $record): bool => ! $record->safeGroup->is_api_integration && $record->is_active)
                        ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('create-transfer', [
                            'safe_id' => $record->id,
                        ])),

                    Action::make('exchange')
                        ->label('Döviz Çıkışı')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('warning')
                        ->visible(fn (Safe $record): bool => ! $record->safeGroup->is_api_integration && $record->is_active)
                        ->url(fn (Safe $record): string => SafeTransactionResource::getUrl('create-exchange', [
                            'safe_id' => $record->id,
                        ])),

                    ViewAction::make()->label('Görüntüle'),
                    EditAction::make()->label('Düzenle'),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri Sil'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSafes::route('/'),
            'create' => Pages\CreateSafe::route('/create'),
            'view'   => Pages\ViewSafe::route('/{record}'),
            'edit'   => Pages\EditSafe::route('/{record}/edit'),
        ];
    }
}
