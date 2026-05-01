<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ContactType;
use App\Filament\Resources\ContactCategoryResource\Pages;
use App\Models\ContactCategory;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup as TableBulkActionGroup;
use Filament\Actions\DeleteBulkAction as TableDeleteBulkAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactCategoryResource extends Resource
{
    protected static ?string $model = ContactCategory::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kişiler';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Kişi Kategorisi';

    protected static ?string $modelLabel = 'Kişi Kategorisi';

    protected static ?string $pluralModelLabel = 'Kişi Kategorileri';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_active', true)->count();
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Kategori Adı')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Kategori adı'),

                                ColorPicker::make('color')
                                    ->label('Renk')
                                    ->nullable(),

                                Select::make('contact_type')
                                    ->label('Sistem Türü')
                                    ->options(function (?ContactCategory $record): array {
                                        $usedTypes = ContactCategory::query()
                                            ->whereNotNull('contact_type')
                                            ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                                            ->pluck('contact_type')
                                            ->map(fn ($v) => $v instanceof ContactType ? $v->value : $v)
                                            ->toArray();

                                        return collect(ContactType::cases())
                                            ->reject(fn (ContactType $t) => in_array($t->value, $usedTypes, true))
                                            ->mapWithKeys(fn (ContactType $t) => [$t->value => $t->label()])
                                            ->toArray();
                                    })
                                    ->nullable()
                                    ->placeholder('Genel kategori')
                                    ->helperText('Sistem davranışı tetiklemesi için seçin. Her türden yalnızca bir kategori olabilir.'),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),

                                Textarea::make('description')
                                    ->label('Açıklama')
                                    ->nullable()
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori Adı')
                    ->searchable()
                    ->sortable(),

                ColorColumn::make('color')
                    ->label('Renk'),

                TextColumn::make('contact_type')
                    ->label('Sistem Türü')
                    ->formatStateUsing(fn (?ContactType $state): string => $state?->label() ?? '-')
                    ->badge()
                    ->color(fn (?ContactType $state): string => match ($state) {
                        ContactType::DONOR         => 'info',
                        ContactType::AID_RECIPIENT => 'success',
                        ContactType::STUDENT       => 'warning',
                        default                    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('contacts_count')
                    ->label('Kişi Sayısı')
                    ->counts('contacts')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktif Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),
            ])
            ->actions([
                ViewAction::make()->label('Görüntüle'),
                EditAction::make()->label('Düzenle'),
                DeleteAction::make()->label('Sil'),
            ])
            ->bulkActions([
                TableBulkActionGroup::make([
                    TableDeleteBulkAction::make()->label('Seçilenleri Sil'),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContactCategories::route('/'),
            'create' => Pages\CreateContactCategory::route('/create'),
            'edit'   => Pages\EditContactCategory::route('/{record}/edit'),
            'view'   => Pages\ViewContactCategory::route('/{record}'),
        ];
    }
}
