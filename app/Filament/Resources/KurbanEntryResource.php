<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\LivestockType;
use App\Filament\Resources\KurbanEntryResource\Pages;
use App\Models\Contact;
use App\Models\KurbanEntry;
use App\Models\KurbanList;
use App\Models\SafeTransactionCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KurbanEntryResource extends Resource
{
    protected static ?string $model = KurbanEntry::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kurban';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Kayıtlar';

    protected static ?string $modelLabel = 'Kurban Kaydı';

    protected static ?string $pluralModelLabel = 'Kurban Kayıtları';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Kişi Bilgileri')
                    ->icon('heroicon-o-user')
                    ->description('Bu kurban kaydının sahibi.')
                    ->schema([
                        Select::make('contact_id')
                            ->label('Kişi')
                            ->required()
                            ->options(fn () => Contact::query()
                                ->with('region')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Contact $c): array => [
                                    $c->id => $c->first_name.' '.$c->last_name
                                        .($c->phone ? ' — '.$c->phone : '')
                                        .($c->region ? ' — '.$c->region->name : ''),
                                ])
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-user')
                            ->columnSpanFull(),
                    ]),

                Section::make('Kurban Bilgileri')
                    ->icon('heroicon-o-banknotes')
                    ->description('Kurbanla ilgili detay bilgiler.')
                    ->columns(2)
                    ->schema([
                        // Satır 1: Sıra No + Liste
                        TextInput::make('queue_number')
                            ->label('Sıra No')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?KurbanEntry $record) => $record !== null),

                        Select::make('kurban_list_id')
                            ->label('Liste')
                            ->options(fn () => KurbanList::query()
                                ->with(['season', 'collector'])
                                ->get()
                                ->mapWithKeys(fn (KurbanList $l) => [$l->id => $l->getTitle()])
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->prefixIcon('heroicon-o-list-bullet')
                            ->afterStateUpdated(function (?int $state, Set $set): void {
                                if ($state === null) {
                                    return;
                                }

                                $list = KurbanList::with('season')->find($state);

                                if ($list?->season?->default_livestock_type !== null) {
                                    $set('livestock_type', $list->season->default_livestock_type->value);
                                }
                            }),

                        // Satır 2: Hayvan Türü + Kurban Türü
                        Select::make('livestock_type')
                            ->label('Hayvan Türü (Hisse)')
                            ->required()
                            ->options(collect(LivestockType::cases())->mapWithKeys(fn (LivestockType $t) => [$t->value => $t->label()])->toArray())
                            ->default(LivestockType::LARGE->value)
                            ->prefixIcon('heroicon-o-tag'),

                        Select::make('sacrifice_category_id')
                            ->label('Kurban Türü')
                            ->required()
                            ->options(fn () => SafeTransactionCategory::query()
                                ->where('is_sacrifice_type', true)
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn ($c) => [$c->id => $c->name])
                                ->toArray()
                            )
                            ->searchable()
                            ->prefixIcon('heroicon-o-tag'),

                        // Satır 3: Açıklama
                        Textarea::make('notes')
                            ->label('Açıklama')
                            ->nullable()
                            ->rows(3)
                            ->placeholder('Varsa özel notlar...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('queue_number')
                    ->label('Sıra No')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('contact.first_name')
                    ->label('Ad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact.last_name')
                    ->label('Soyad')
                    ->searchable(),

                TextColumn::make('contact.phone')
                    ->label('Telefon')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('livestock_type')
                    ->label('Hayvan Türü')
                    ->formatStateUsing(fn ($state) => $state instanceof LivestockType ? $state->label() : $state)
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof LivestockType ? $state : null) {
                        LivestockType::SMALL => 'warning',
                        LivestockType::LARGE => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('sacrificeCategory.name')
                    ->label('Kurban Türü'),

                TextColumn::make('list.id')
                    ->label('Liste')
                    ->formatStateUsing(fn ($record) => $record->list?->getTitle()),

                TextColumn::make('list.collector.name')
                    ->label('Toplayıcı'),

                IconColumn::make('is_paid')
                    ->label('Ödendi')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('paid_date')
                    ->label('Ödeme Tarihi')
                    ->date('d.m.Y')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Eklenme')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginationPageOptions([20, 50, 100])
            ->defaultPaginationPageOption(20)
            ->defaultSort('queue_number', 'desc')
            ->filters([
                TernaryFilter::make('is_paid')
                    ->label('Ödeme Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Ödendi')
                    ->falseLabel('Ödenmedi'),

                SelectFilter::make('sacrifice_category_id')
                    ->label('Kurban Türü')
                    ->relationship('sacrificeCategory', 'name')
                    ->searchable(),

                SelectFilter::make('kurban_list_id')
                    ->label('Liste')
                    ->options(fn () => KurbanList::query()
                        ->with(['season', 'collector'])
                        ->get()
                        ->mapWithKeys(fn (KurbanList $l) => [$l->id => $l->getTitle()])
                        ->toArray()
                    )
                    ->searchable(),
            ])
            ->actions([
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
            'index' => Pages\ListKurbanEntries::route('/'),
            'create' => Pages\CreateKurbanEntry::route('/create'),
            'edit' => Pages\EditKurbanEntry::route('/{record}/edit'),
        ];
    }
}
