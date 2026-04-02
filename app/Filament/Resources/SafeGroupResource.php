<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SafeGroupResource\Pages;
use App\Models\SafeGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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

class SafeGroupResource extends Resource
{
    protected static ?string $model = SafeGroup::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kasa';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Kasa Grupları';

    protected static ?string $modelLabel = 'Kasa Grubu';

    protected static ?string $pluralModelLabel = 'Kasa Grupları';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Kasa Grubu Bilgileri')
                    ->icon('heroicon-o-folder')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Grup Adı')
                                    ->required()
                                    ->maxLength(255),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ]),

                        Toggle::make('is_api_integration')
                            ->label('API Entegrasyonu')
                            ->default(false)
                            ->live()
                            ->inlineLabel(),

                        Textarea::make('credentials')
                            ->label('API Bilgileri')
                            ->rows(4)
                            ->hint('JSON formatında API erişim bilgileri')
                            ->visible(fn ($get) => (bool) $get('is_api_integration'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Grup Adı')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                IconColumn::make('is_api_integration')
                    ->label('API')
                    ->boolean(),

                TextColumn::make('safes_count')
                    ->label('Kasa Sayısı')
                    ->counts('safes'),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginationPageOptions([20, 50, 100])
            ->defaultPaginationPageOption(20)
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),

                TernaryFilter::make('is_api_integration')
                    ->label('API Entegrasyonu')
                    ->placeholder('Tümü')
                    ->trueLabel('Evet')
                    ->falseLabel('Hayır'),
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
            'index' => Pages\ListSafeGroups::route('/'),
            'create' => Pages\CreateSafeGroup::route('/create'),
            'view' => Pages\ViewSafeGroup::route('/{record}'),
            'edit' => Pages\EditSafeGroup::route('/{record}/edit'),
        ];
    }
}
