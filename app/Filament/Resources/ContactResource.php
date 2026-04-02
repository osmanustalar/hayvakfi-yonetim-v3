<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\LivestockType;
use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use App\Models\Region;
use App\Models\KurbanEntry;
use App\Models\KurbanList;
use App\Models\KurbanSeason;
use App\Models\SafeTransactionCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
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
            'Bölge'      => $record->region?->name ?? '-',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'phone'];
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
                                    ->placeholder('+90 555 123 45 67')
                                    ->maxLength(30)
                                    ->nullable(),

                                DatePicker::make('birth_date')
                                    ->label('Doğum Tarihi')
                                    ->displayFormat('d.m.Y')
                                    ->nullable(),

                                Select::make('region_id')
                                    ->label('Bölge')
                                    ->nullable()
                                    ->options(fn () => Region::query()
                                        ->where('is_active', true)
                                        ->orderBy('sort_order')
                                        ->get()
                                        ->mapWithKeys(fn (Region $r) => [$r->id => $r->name])
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-map-pin'),
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

                        Repeater::make('phones')
                            ->label('Ek Telefon Numaraları')
                            ->relationship('phones')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('phone')
                                            ->label('Telefon')
                                            ->required()
                                            ->maxLength(30)
                                            ->placeholder('+49 170 123 45 67'),

                                        TextInput::make('label')
                                            ->label('Etiket')
                                            ->nullable()
                                            ->maxLength(50)
                                            ->placeholder('ev, iş, mobil...'),
                                    ]),
                            ])
                            ->addActionLabel('Telefon Ekle')
                            ->reorderable(false)
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

                TextColumn::make('region.name')
                    ->label('Bölge')
                    ->searchable()
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
                Action::make('addToKurbanList')
                    ->label('Kurban Listesine Ekle')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->visible(fn (): bool => KurbanSeason::query()
                        ->where('company_id', session('active_company_id'))
                        ->where('is_active', true)
                        ->exists()
                    )
                    ->form(function (): array {
                        $activeSeason = KurbanSeason::query()
                            ->where('company_id', session('active_company_id'))
                            ->where('is_active', true)
                            ->with('lists.season')
                            ->first();

                        $lists = $activeSeason?->lists ?? collect();

                        return [
                            Select::make('kurban_list_id')
                                ->label('Kurban Listesi')
                                ->required()
                                ->options(
                                    $lists->mapWithKeys(fn (KurbanList $l): array => [
                                        $l->id => ($l->season?->year ?? '?') . ' — ' . ($l->collector?->name ?? 'Toplayıcı yok'),
                                    ])->toArray()
                                )
                                ->live()
                                ->afterStateUpdated(function (?int $state, \Filament\Schemas\Components\Utilities\Set $set) use ($activeSeason): void {
                                    if ($state === null) {
                                        return;
                                    }
                                    $list = KurbanList::with('season')->find($state);
                                    if ($list?->season?->default_livestock_type !== null) {
                                        $set('livestock_type', $list->season->default_livestock_type->value);
                                    }
                                })
                                ->searchable()
                                ->prefixIcon('heroicon-o-list-bullet'),

                            Select::make('livestock_type')
                                ->label('Hayvan Türü (Hisse)')
                                ->required()
                                ->options(collect(LivestockType::cases())->mapWithKeys(fn (LivestockType $t) => [$t->value => $t->label()])->toArray())
                                ->default(fn (): string => $activeSeason?->default_livestock_type?->value ?? LivestockType::LARGE->value)
                                ->prefixIcon('heroicon-o-tag'),

                            Select::make('sacrifice_category_id')
                                ->label('Kurban Türü')
                                ->required()
                                ->options(
                                    SafeTransactionCategory::query()
                                        ->where('is_sacrifice_type', true)
                                        ->where('is_active', true)
                                        ->orderBy('sort_order')
                                        ->get()
                                        ->mapWithKeys(fn ($c) => [$c->id => $c->name])
                                        ->toArray()
                                )
                                ->searchable()
                                ->prefixIcon('heroicon-o-tag'),

                            Textarea::make('notes')
                                ->label('Notlar')
                                ->nullable()
                                ->rows(3),
                        ];
                    })
                    ->action(function (\App\Models\Contact $record, array $data): void {
                        KurbanEntry::create([
                            'company_id'             => session('active_company_id'),
                            'kurban_list_id'         => (int) $data['kurban_list_id'],
                            'contact_id'             => $record->id,
                            'sacrifice_category_id'  => (int) $data['sacrifice_category_id'],
                            'livestock_type'         => $data['livestock_type'],
                            'notes'                  => $data['notes'] ?? null,
                            'created_user_id'        => auth()->id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Kurban listesine eklendi')
                            ->body($record->first_name . ' ' . $record->last_name . ' kurban listesine kaydedildi.')
                            ->send();
                    })
                    ->modalHeading(fn (\App\Models\Contact $record): string => $record->first_name . ' ' . $record->last_name . ' — Kurban Listesine Ekle')
                    ->modalSubmitActionLabel('Listeye Ekle')
                    ->modalCancelActionLabel('İptal'),
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
