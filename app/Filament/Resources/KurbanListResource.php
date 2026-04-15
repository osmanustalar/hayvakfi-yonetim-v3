<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\KurbanListResource\Pages;
use App\Filament\Resources\KurbanListResource\RelationManagers\EntriesRelationManager;
use App\Models\KurbanList;
use App\Filament\Resources\KurbanEntryResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KurbanListResource extends Resource
{
    protected static ?string $model = KurbanList::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kurban';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Listeler';

    protected static ?string $modelLabel = 'Kurban Listesi';

    protected static ?string $pluralModelLabel = 'Kurban Listeleri';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Liste Bilgileri')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('kurban_season_id')
                                    ->label('Sezon')
                                    ->relationship('season', 'year')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->year.' Yılı Kurban Sezonu')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('collector_user_id')
                                    ->label('Toplayıcı')
                                    ->relationship('collector', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ]),

                        Textarea::make('description')
                            ->label('Açıklama')
                            ->nullable()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('season.year')
                    ->label('Sezon')
                    ->formatStateUsing(fn ($state) => $state.' Yılı')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('collector.name')
                    ->label('Toplayıcı')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('total_shares')
                    ->label('Toplam Hisse')
                    ->getStateUsing(fn ($record) => $record->total_shares)
                    ->badge()
                    ->color('gray'),

                TextColumn::make('total_paid_shares')
                    ->label('Ödenen Hisse')
                    ->getStateUsing(fn ($record) => $record->total_paid_shares)
                    ->badge()
                    ->color('success'),

                TextColumn::make('remaining_shares')
                    ->label('Kalan Hisse')
                    ->getStateUsing(fn ($record) => $record->remaining_shares)
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Oluşturma')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginationPageOptions([20, 50, 100])
            ->defaultPaginationPageOption(20)
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('kurban_season_id')
                    ->label('Sezon')
                    ->relationship('season', 'year')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->year.' Yılı'),

                SelectFilter::make('collector_user_id')
                    ->label('Toplayıcı')
                    ->relationship('collector', 'name'),
            ])
            ->recordUrl(fn (KurbanList $record): string =>
                KurbanEntryResource::getUrl('index', [
                    'filters' => [
                        'kurban_list_id' => ['value' => (string) $record->id],
                    ],
                ])
            )
            ->actions([
                EditAction::make()->label('Düzenle'),
                Action::make('bulk_payment')
                    ->label('Toplu Tahsilat Al')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Select::make('safe_id')
                            ->label('Kasa / Banka')
                            ->options(\App\Models\Safe::query()
                                ->whereHas('safeGroup', fn ($q) => $q->where('is_api_integration', false))
                                ->pluck('name', 'id')
                            )
                            ->required(),
                        \Filament\Forms\Components\Select::make('transaction_category_id')
                            ->label('Tahsilat Kategorisi (Kurban Türü)')
                            ->options(\App\Models\SafeTransactionCategory::query()
                                ->where('is_sacrifice_type', true)
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                            )
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('share_count')
                            ->label('Tahsil Edilen Hisse Adedi')
                            ->numeric()
                            ->default(fn ($record) => $record->remaining_shares)
                            ->maxValue(fn ($record) => $record->remaining_shares)
                            ->minValue(1)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Toplam Tutar')
                            ->numeric()
                            ->required(),
                        \Filament\Forms\Components\DatePicker::make('process_date')
                            ->label('İşlem Tarihi')
                            ->default(now())
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('description')
                            ->label('Açıklama')
                            ->nullable(),
                    ])
                    ->action(function (KurbanList $record, array $data) {
                        app(\App\Services\SafeTransactionService::class)->create([
                            'safe_id' => $data['safe_id'],
                            'type' => \App\Enums\TransactionType::INCOME->value,
                            'total_amount' => $data['amount'],
                            'share_count' => $data['share_count'],
                            'kurban_list_id' => $record->id,
                            'process_date' => $data['process_date'],
                            'description' => $data['description'],
                            'items' => [
                                [
                                    'transaction_category_id' => $data['transaction_category_id'],
                                    'amount' => $data['amount'],
                                ]
                            ]
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Toplu tahsilat başarıyla alındı.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (KurbanList $record) => $record->remaining_shares > 0),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri Sil'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKurbanLists::route('/'),
            'create' => Pages\CreateKurbanList::route('/create'),
            'view' => Pages\ViewKurbanList::route('/{record}'),
            'edit' => Pages\EditKurbanList::route('/{record}/edit'),
        ];
    }
}
