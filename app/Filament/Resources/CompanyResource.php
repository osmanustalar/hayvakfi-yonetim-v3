<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
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
use Illuminate\Database\Eloquent\Model;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Yönetim';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Şirketler';

    protected static ?string $modelLabel = 'Şirket';

    protected static ?string $pluralModelLabel = 'Şirketler';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Company $record */
        return [
            'Vergi No' => $record->tax_number ?? '-',
            'Telefon' => $record->phone ?? '-',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'tax_number', 'phone'];
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Şirket Bilgileri')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Şirket Adı')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Şirket adını girin'),

                                TextInput::make('tax_number')
                                    ->label('Vergi Numarası')
                                    ->maxLength(20)
                                    ->placeholder('1234567890'),

                                TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('05XX XXX XX XX'),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ]),

                        Textarea::make('address')
                            ->label('Adres')
                            ->rows(3)
                            ->placeholder('Şirket adresi')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Şirket Adı')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tax_number')
                    ->label('Vergi No')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Telefon')
                    ->copyable()
                    ->toggleable(),

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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
