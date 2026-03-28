<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kişiler';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Kişiler';

    protected static ?string $modelLabel = 'Kişi';

    protected static ?string $pluralModelLabel = 'Kişiler';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Contact $record */
        return [
            'Telefon'    => $record->phone ?? '-',
            'TC Kimlik'  => $record->national_id ?? '-',
            'Şehir'      => $record->city ?? '-',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'phone', 'national_id'];
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Kişisel Bilgiler')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('Ad')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Ad'),

                                TextInput::make('last_name')
                                    ->label('Soyad')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Soyad'),

                                TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->placeholder('05XX XXX XX XX')
                                    ->maxLength(20),

                                TextInput::make('national_id')
                                    ->label('TC Kimlik No')
                                    ->maxLength(20)
                                    ->placeholder('11 haneli TC Kimlik'),

                                DatePicker::make('birth_date')
                                    ->label('Doğum Tarihi')
                                    ->displayFormat('d.m.Y')
                                    ->nullable(),

                                TextInput::make('city')
                                    ->label('Şehir')
                                    ->maxLength(100)
                                    ->placeholder('İstanbul'),
                            ]),

                        Textarea::make('address')
                            ->label('Adres')
                            ->rows(3)
                            ->placeholder('Açık adres')
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Notlar')
                            ->rows(3)
                            ->placeholder('Ek notlar...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Kategoriler')
                    ->icon('heroicon-o-tag')
                    ->description('Kişinin hangi kategorilere dahil olduğunu belirtin.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_donor')
                                    ->label('Bağışçı')
                                    ->default(false),

                                Toggle::make('is_aid_recipient')
                                    ->label('Yardım Alan')
                                    ->default(false),

                                Toggle::make('is_student')
                                    ->label('Öğrenci')
                                    ->default(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Ad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('last_name')
                    ->label('Soyad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('national_id')
                    ->label('TC Kimlik')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('city')
                    ->label('Şehir')
                    ->toggleable(),

                IconColumn::make('is_donor')
                    ->label('Bağışçı')
                    ->boolean(),

                IconColumn::make('is_aid_recipient')
                    ->label('Yardım Alan')
                    ->boolean(),

                IconColumn::make('is_student')
                    ->label('Öğrenci')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginationPageOptions([20, 50, 100])
            ->defaultPaginationPageOption(20)
            ->filters([
                Filter::make('is_donor')
                    ->label('Bağışçılar')
                    ->query(fn (Builder $query): Builder => $query->where('is_donor', true)),

                Filter::make('is_aid_recipient')
                    ->label('Yardım Alanlar')
                    ->query(fn (Builder $query): Builder => $query->where('is_aid_recipient', true)),

                Filter::make('is_student')
                    ->label('Öğrenciler')
                    ->query(fn (Builder $query): Builder => $query->where('is_student', true)),

                TernaryFilter::make('phone')
                    ->label('Telefon Durumu')
                    ->nullable()
                    ->placeholder('Tümü')
                    ->trueLabel('Telefonu var')
                    ->falseLabel('Telefonu yok')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('phone'),
                        false: fn (Builder $query): Builder => $query->whereNull('phone'),
                    ),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view'   => Pages\ViewContact::route('/{record}'),
            'edit'   => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
