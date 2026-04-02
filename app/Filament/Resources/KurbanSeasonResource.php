<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\LivestockType;
use App\Filament\Resources\KurbanSeasonResource\Pages;
use App\Models\KurbanSeason;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KurbanSeasonResource extends Resource
{
    protected static ?string $model = KurbanSeason::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kurban';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Sezonlar';

    protected static ?string $modelLabel = 'Kurban Sezonu';

    protected static ?string $pluralModelLabel = 'Kurban Sezonları';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Sezon Bilgileri')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('year')
                                    ->label('Yıl')
                                    ->required()
                                    ->numeric()
                                    ->minValue(2020)
                                    ->maxValue(2050),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),

                                TextInput::make('price_try')
                                    ->label('Kurban Ücreti (TRY)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('₺'),

                                TextInput::make('price_eur')
                                    ->label('Kurban Ücreti (EUR)')
                                    ->nullable()
                                    ->numeric()
                                    ->prefix('€'),

                                Select::make('default_livestock_type')
                                    ->label('Varsayılan Hayvan Türü')
                                    ->required()
                                    ->options(collect(LivestockType::cases())->mapWithKeys(fn (LivestockType $t) => [$t->value => $t->label()])->toArray())
                                    ->default(LivestockType::LARGE->value)
                                    ->prefixIcon('heroicon-o-tag'),
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
                TextColumn::make('year')
                    ->label('Sezon')
                    ->formatStateUsing(fn ($state) => $state . ' Yılı Kurban Sezonu')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('default_livestock_type')
                    ->label('Hayvan Türü')
                    ->formatStateUsing(fn ($state) => $state instanceof LivestockType ? $state->label() : $state)
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof LivestockType ? $state : null) {
                        LivestockType::SMALL => 'warning',
                        LivestockType::LARGE => 'success',
                        default             => 'gray',
                    }),

                TextColumn::make('price_try')
                    ->label('Ücret (TRY)')
                    ->money('TRY'),

                TextColumn::make('price_eur')
                    ->label('Ücret (EUR)')
                    ->money('EUR'),

                TextColumn::make('lists_count')
                    ->label('Liste Sayısı')
                    ->counts('lists'),

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
            ->defaultSort('year', 'desc')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktif')
                    ->placeholder('Tümü')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListKurbanSeasons::route('/'),
            'create' => Pages\CreateKurbanSeason::route('/create'),
            'view'   => Pages\ViewKurbanSeason::route('/{record}'),
            'edit'   => Pages\EditKurbanSeason::route('/{record}/edit'),
        ];
    }
}
