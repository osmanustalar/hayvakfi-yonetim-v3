<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AidRecordResource\Pages;
use App\Models\AidRecord;
use App\Models\Contact;
use App\Models\SafeTransaction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AidRecordResource extends Resource
{
    protected static ?string $model = AidRecord::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Yardım';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Yardım Kayıtları';

    protected static ?string $modelLabel = 'Yardım Kaydı';

    protected static ?string $pluralModelLabel = 'Yardım Kayıtları';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Yardım Bilgileri')
                    ->icon('heroicon-o-gift')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('contact_id')
                                    ->label('Yardım Alan Kişi')
                                    ->required()
                                    ->options(fn () => Contact::query()
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Contact $c) => [
                                            $c->id => $c->first_name . ' ' . $c->last_name,
                                        ])
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-user'),

                                TextInput::make('aid_type')
                                    ->label('Yardım Türü')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Örn: Gıda, Giyim, Nakit vb.'),

                                TextInput::make('amount')
                                    ->label('Tutar')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('₺')
                                    ->placeholder('0.00'),

                                DatePicker::make('given_at')
                                    ->label('Verilme Tarihi')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d.m.Y'),

                                Select::make('transaction_id')
                                    ->label('Bağlı Kasa İşlemi (İsteğe Bağlı)')
                                    ->nullable()
                                    ->options(fn () => SafeTransaction::query()
                                        ->with('safe')
                                        ->orderBy('process_date', 'desc')
                                        ->limit(100)
                                        ->get()
                                        ->mapWithKeys(fn (SafeTransaction $t) => [
                                            $t->id => $t->safe->name . ' - ' . $t->total_amount . ' TRY - ' . $t->process_date->format('d.m.Y'),
                                        ])
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-banknotes'),
                            ]),

                        Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(3)
                            ->placeholder('Yardım hakkında detaylı açıklama...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('contact.first_name')
                    ->label('Yardım Alan Adı')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact.last_name')
                    ->label('Soyad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('aid_type')
                    ->label('Yardım Türü')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Tutar')
                    ->money('TRY')
                    ->sortable(),

                TextColumn::make('given_at')
                    ->label('Verilme Tarihi')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('transaction.safe.name')
                    ->label('Kasa')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('createdBy.name')
                    ->label('Oluşturan')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('given_at', 'desc')
            ->filters([
                //
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAidRecords::route('/'),
            'create' => Pages\CreateAidRecord::route('/create'),
            'edit' => Pages\EditAidRecord::route('/{record}/edit'),
        ];
    }
}
