<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\FeeStatus;
use App\Filament\Resources\StudentFeeResource\Pages;
use App\Models\Safe;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Services\StudentFeeService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StudentFeeResource extends Resource
{
    protected static ?string $model = StudentFee::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Eğitim';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Aidatlar';

    protected static ?string $modelLabel = 'Aidat';

    protected static ?string $pluralModelLabel = 'Aidatlar';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Aidat Bilgileri')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('enrollment_id')
                                    ->label('Öğrenci Kaydı')
                                    ->required()
                                    ->options(fn () => StudentEnrollment::query()
                                        ->with(['contact', 'schoolClass'])
                                        ->get()
                                        ->mapWithKeys(fn (StudentEnrollment $e) => [
                                            $e->id => $e->contact->first_name . ' ' . $e->contact->last_name . ' - ' . $e->schoolClass->name,
                                        ])
                                        ->toArray()
                                    )
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-user'),

                                DatePicker::make('period')
                                    ->label('Dönem (Ayın 1\'i)')
                                    ->required()
                                    ->displayFormat('m/Y')
                                    ->helperText('Örn: 2026-03-01 = Mart 2026'),

                                TextInput::make('amount')
                                    ->label('Tutar')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('₺')
                                    ->placeholder('0.00'),

                                DatePicker::make('due_date')
                                    ->label('Son Ödeme Tarihi')
                                    ->required()
                                    ->displayFormat('d.m.Y'),

                                Select::make('status')
                                    ->label('Durum')
                                    ->required()
                                    ->options(FeeStatus::class)
                                    ->default(FeeStatus::PENDING->value)
                                    ->prefixIcon('heroicon-o-flag'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('enrollment.contact.first_name')
                    ->label('Öğrenci Adı')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('enrollment.contact.last_name')
                    ->label('Soyad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('enrollment.schoolClass.name')
                    ->label('Sınıf')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('period')
                    ->label('Dönem')
                    ->date('m/Y')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Tutar')
                    ->money('TRY')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Son Ödeme')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (FeeStatus $state): string => match ($state) {
                        FeeStatus::PENDING => 'warning',
                        FeeStatus::PAID => 'success',
                        FeeStatus::OVERDUE => 'danger',
                        FeeStatus::WAIVED => 'gray',
                    })
                    ->formatStateUsing(fn (FeeStatus $state): string => $state->label())
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label('Ödeme Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('period', 'desc')
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('pay')
                        ->label('Öde')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->form([
                            Select::make('safe_id')
                                ->label('Kasa')
                                ->required()
                                ->options(fn () => Safe::query()
                                    ->where('is_active', true)
                                    ->whereHas('safeGroup', fn ($q) => $q->where('is_api_integration', false))
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Safe $s) => [$s->id => $s->name])
                                    ->toArray()
                                )
                                ->searchable(),

                            DatePicker::make('process_date')
                                ->label('Ödeme Tarihi')
                                ->required()
                                ->default(now())
                                ->displayFormat('d.m.Y')
                                ->native(false),
                        ])
                        ->action(function (Model $record, array $data): void {
                            try {
                                app(StudentFeeService::class)->markAsPaid($record->id, $data['safe_id'], $data['process_date']);

                                Notification::make()
                                    ->success()
                                    ->title('Aidat ödeme işlemi başarılı')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Hata')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        })
                        ->visible(fn (Model $record): bool => $record->status !== FeeStatus::PAID),
                ]),
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
            'index' => Pages\ListStudentFees::route('/'),
            'create' => Pages\CreateStudentFee::route('/create'),
            'edit' => Pages\EditStudentFee::route('/{record}/edit'),
        ];
    }
}
