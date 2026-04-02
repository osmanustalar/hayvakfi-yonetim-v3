<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\KurbanListResource\Pages;
use App\Filament\Resources\KurbanListResource\RelationManagers\EntriesRelationManager;
use App\Models\KurbanList;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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

                TextColumn::make('entries_count')
                    ->label('Toplam Kayıt')
                    ->counts('entries')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('paid_entries_count')
                    ->label('Ödenen')
                    ->getStateUsing(fn ($record) => $record->entries()->where('is_paid', true)->count())
                    ->badge()
                    ->color('success'),

                TextColumn::make('unpaid_entries_count')
                    ->label('Ödenmemiş')
                    ->getStateUsing(fn ($record) => $record->entries()->where('is_paid', false)->count())
                    ->badge()
                    ->color('danger'),

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
            ->actions([
                ViewAction::make()->label('Görüntüle'),
                EditAction::make()->label('Düzenle'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Seçilenleri Sil'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EntriesRelationManager::class,
        ];
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
