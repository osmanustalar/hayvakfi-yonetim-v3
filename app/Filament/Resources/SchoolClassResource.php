<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolClassResource\Pages;
use App\Models\SchoolClass;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SchoolClassResource extends Resource
{
    protected static ?string $model = SchoolClass::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Eğitim';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Sınıflar';

    protected static ?string $modelLabel = 'Sınıf';

    protected static ?string $pluralModelLabel = 'Sınıflar';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Sınıf Bilgileri')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Sınıf Adı')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Örn: 5-A'),

                                Select::make('teacher_id')
                                    ->label('Öğretmen')
                                    ->nullable()
                                    ->options(fn () => User::query()
                                        ->where('company_id', session('active_company_id'))
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (User $u) => [$u->id => $u->name])
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-user'),

                                DatePicker::make('start_date')
                                    ->label('Başlangıç Tarihi')
                                    ->required()
                                    ->displayFormat('d.m.Y'),

                                DatePicker::make('end_date')
                                    ->label('Bitiş Tarihi')
                                    ->required()
                                    ->displayFormat('d.m.Y'),

                                TextInput::make('default_monthly_fee')
                                    ->label('Varsayılan Aylık Aidat')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('₺')
                                    ->placeholder('0.00'),

                                TextInput::make('capacity')
                                    ->label('Kapasite')
                                    ->nullable()
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('Maksimum öğrenci sayısı'),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Sınıf Adı')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('teacher.name')
                    ->label('Öğretmen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Başlangıç')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Bitiş')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('default_monthly_fee')
                    ->label('Aylık Aidat')
                    ->money('TRY')
                    ->sortable(),

                TextColumn::make('capacity')
                    ->label('Kapasite')
                    ->sortable()
                    ->toggleable(),

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
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListSchoolClasses::route('/'),
            'create' => Pages\CreateSchoolClass::route('/create'),
            'edit' => Pages\EditSchoolClass::route('/{record}/edit'),
        ];
    }
}
