<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\StudentEnrollmentResource\Pages;
use App\Models\Contact;
use App\Models\SchoolClass;
use App\Models\StudentEnrollment;
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

class StudentEnrollmentResource extends Resource
{
    protected static ?string $model = StudentEnrollment::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Eğitim';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Öğrenci Kayıtları';

    protected static ?string $modelLabel = 'Öğrenci Kaydı';

    protected static ?string $pluralModelLabel = 'Öğrenci Kayıtları';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Öğrenci Bilgileri')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('class_id')
                                    ->label('Sınıf')
                                    ->required()
                                    ->options(fn () => SchoolClass::query()
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (SchoolClass $c) => [$c->id => $c->name])
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-academic-cap'),

                                Select::make('contact_id')
                                    ->label('Öğrenci')
                                    ->required()
                                    ->options(fn () => Contact::query()
                                        ->orderBy('first_name')
                                        ->get()
                                        ->mapWithKeys(fn (Contact $c) => [$c->id => $c->first_name . ' ' . $c->last_name])
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-user'),

                                DatePicker::make('enrollment_date')
                                    ->label('Kayıt Tarihi')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d.m.Y'),

                                TextInput::make('monthly_fee')
                                    ->label('Aylık Aidat (isteğe bağlı)')
                                    ->nullable()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('₺')
                                    ->helperText('Boş bırakılırsa sınıfın varsayılan aidatı kullanılır')
                                    ->placeholder('0.00'),

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
                TextColumn::make('contact.first_name')
                    ->label('Ad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact.last_name')
                    ->label('Soyad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('schoolClass.name')
                    ->label('Sınıf')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('enrollment_date')
                    ->label('Kayıt Tarihi')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('monthly_fee')
                    ->label('Aylık Aidat')
                    ->money('TRY')
                    ->sortable()
                    ->placeholder('Varsayılan'),

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
            'index' => Pages\ListStudentEnrollments::route('/'),
            'create' => Pages\CreateStudentEnrollment::route('/create'),
            'edit' => Pages\EditStudentEnrollment::route('/{record}/edit'),
        ];
    }
}
