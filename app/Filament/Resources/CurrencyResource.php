<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
use Illuminate\Database\Eloquent\Model;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Yönetim > Tanımlar';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Para Birimleri';

    protected static ?string $modelLabel = 'Para Birimi';

    protected static ?string $pluralModelLabel = 'Para Birimleri';

    protected static ?int $navigationSort = 10;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Currency $record */
        return [
            'Sembol' => $record->symbol,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'symbol'];
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Para Birimi Bilgileri')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Kod')
                                    ->placeholder('TRY')
                                    ->maxLength(10)
                                    ->required()
                                    ->helperText('ISO 4217 para birimi kodu (örn: TRY, USD, EUR)'),

                                TextInput::make('symbol')
                                    ->label('Sembol')
                                    ->placeholder('₺')
                                    ->maxLength(5)
                                    ->required()
                                    ->helperText('Para birimi sembolü (örn: ₺, $, €)'),
                            ]),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kod')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('symbol')
                    ->label('Sembol'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Durum')
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
            ])
            ->paginationPageOptions([20, 50, 100])
            ->defaultPaginationPageOption(20)
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'view'   => Pages\ViewCurrency::route('/{record}'),
            'edit'   => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
