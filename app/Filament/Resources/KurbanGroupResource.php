<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\KurbanGroupResource\Pages;
use App\Filament\Resources\KurbanEntryResource;
use App\Models\KurbanGroup;
use App\Models\KurbanList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KurbanGroupResource extends Resource
{
    protected static ?string $model = KurbanGroup::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kurban';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Kurban Grupları';

    protected static ?string $modelLabel = 'Kurban Grubu';

    protected static ?string $pluralModelLabel = 'Kurban Grupları';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Grup Bilgileri')
                    ->schema([
                        Select::make('kurban_season_id')
                            ->label('Sezon')
                            ->relationship('season', 'year')
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notlar')
                            ->columnSpanFull(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('season.year')
                    ->label('Sezon')
                    ->sortable(),
                TextColumn::make('group_no')
                    ->label('Grup No')
                    ->sortable()
                    ->badge(),
                TextColumn::make('entries_count')
                    ->label('Üye Sayısı')
                    ->counts('entries')
                    ->formatStateUsing(fn ($state) => $state . ' / ' . KurbanGroup::MAX_MEMBERS)
                    ->color(fn ($state) => $state >= KurbanGroup::MAX_MEMBERS ? 'danger' : 'success')
                    ->badge(),

                TextColumn::make('entries_details')
                    ->label('Grup Üyeleri')
                    ->html()
                    ->getStateUsing(fn (KurbanGroup $record) => $record->entries->count() > 0 
                        ? '<div style="font-family: inherit; font-size: 0.85em; min-width: 650px; border: 1px solid rgba(0,0,0,0.1); border-radius: 4px; overflow: hidden;">' .
                          '<div style="display: grid; grid-template-columns: 50px 160px 100px 120px 1fr; gap: 10px; background: rgba(0,0,0,0.05); padding: 6px 10px; font-weight: bold; border-bottom: 2px solid rgba(0,0,0,0.1);">' .
                          '<span>Sıra</span><span>Ad Soyad</span><span>Telefon</span><span>Kurban Türü</span><span>Liste</span>' .
                          '</div>' . 
                          $record->entries->map(fn ($e) => 
                            sprintf(
                                '<div style="display: grid; grid-template-columns: 50px 160px 100px 120px 1fr; gap: 10px; padding: 6px 10px; border-bottom: 1px solid rgba(0,0,0,0.05); align-items: center;">' .
                                '<span style="color: #616161;">#%s</span>' .
                                '<span style="font-weight: 600;">%s</span>' .
                                '<span>%s</span>' .
                                '<span>%s</span>' .
                                '<span style="color: #666; font-size: 0.9em;">%s</span>' .
                                '</div>',
                                $e->queue_number,
                                $e->full_name,
                                $e->contact?->phone ?? '-',
                                $e->sacrificeCategory?->name ?? '-',
                                $e->list?->collector?->name ?? '-'
                            )
                          )->implode('') . '</div>'
                        : '<span style="color: #9e9e9e; font-style: italic;">Henüz üye yok</span>'
                    ),
                TextColumn::make('notes')
                    ->label('Notlar')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('entries.list.collector.name')
                    ->label('İlgili Listeler')
                    ->listWithLineBreaks()
                    ->bulleted(),
            ])
            ->filters([
                SelectFilter::make('kurban_list_id')
                    ->label('Liste')
                    ->options(fn () => KurbanList::query()
                        ->with(['season', 'collector'])
                        ->get()
                        ->mapWithKeys(fn (KurbanList $l) => [$l->id => $l->getTitle()])
                        ->toArray()
                    )
                    ->query(fn ($query, array $data) => $data['value'] 
                        ? $query->whereHas('entries', fn ($q) => $q->where('kurban_list_id', $data['value'])) 
                        : $query
                    )
                    ->searchable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->recordUrl(fn (KurbanGroup $record): string =>
                KurbanEntryResource::getUrl('index', [
                    'filters' => [
                        'kurban_season_id' => ['value' => (string) $record->kurban_season_id],
                        'kurban_group_id' => ['value' => (string) $record->id],
                    ],
                ])
            )
            ->searchable();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKurbanGroups::route('/'),
        ];
    }
}
