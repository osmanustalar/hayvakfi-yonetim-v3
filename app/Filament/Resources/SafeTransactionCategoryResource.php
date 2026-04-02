<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SafeTransactionCategoryResource\Pages;
use App\Models\SafeTransactionCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SafeTransactionCategoryResource extends Resource
{
    protected static ?string $model = SafeTransactionCategory::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kasa';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Kategoriler';

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategoriler';

    protected static ?int $navigationSort = 10;

    /**
     * Kategori sorgusu: sistem geneli (company_id IS NULL) + aktif şirket kategorileri.
     * CompanyScope uygulanmaz — SafeTransactionCategory'de CompanyScope yoktur.
     * Sıralama: parent kategorileri işaretleyin, sonra alt kategorileri sort_order'a göre gösterin.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where(function (Builder $q): void {
                $q->whereNull('company_id')
                  ->orWhere('company_id', session('active_company_id'));
            })
            ->orderByRaw('COALESCE(parent_id, id), parent_id IS NULL DESC, sort_order');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Kategori Bilgileri')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Kategori Adı')
                                    ->required(),

                                Select::make('type')
                                    ->label('Tür')
                                    ->nullable()
                                    ->options([
                                        'income'  => 'Gelir',
                                        'expense' => 'Gider',
                                    ]),

                                Select::make('parent_id')
                                    ->label('Üst Kategori')
                                    ->nullable()
                                    ->options(
                                        SafeTransactionCategory::query()
                                            ->whereNull('parent_id')
                                            ->where(function ($q): void {
                                                $q->whereNull('company_id')
                                                  ->orWhere('company_id', session('active_company_id'));
                                            })
                                            ->pluck('name', 'id')
                                    ),

                                Select::make('contact_type')
                                    ->label('Kişi Tipi')
                                    ->nullable()
                                    ->options([
                                        'donor'         => 'Bağışçı',
                                        'aid_recipient' => 'Yardım Alan',
                                        'student'       => 'Öğrenci',
                                    ]),

                                TextInput::make('color')
                                    ->label('Renk (hex)')
                                    ->nullable()
                                    ->maxLength(10),

                                TextInput::make('sort_order')
                                    ->label('Sıra')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),

                                Toggle::make('is_disable_in_report')
                                    ->label('Raporda Gizle')
                                    ->default(false),

                                Toggle::make('is_sacrifice_type')
                                    ->label('Kurban Türü Olarak Seçilebilir')
                                    ->helperText('İşaretlenirse, kurban kaydı girerken bu kategori seçilebilir.')
                                    ->default(false),
                            ]),

                        Textarea::make('description')
                            ->label('Açıklama')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori Adı')
                    ->formatStateUsing(fn ($state, SafeTransactionCategory $record) =>
                        $record->parent_id ? '└── ' . $state : $state
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->color(fn ($state) => match ($state instanceof \App\Enums\TransactionType ? $state->value : $state) {
                        'income'  => 'success',
                        'expense' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state instanceof \App\Enums\TransactionType ? $state->value : $state) {
                        'income'  => 'Gelir',
                        'expense' => 'Gider',
                        default   => '—',
                    }),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                IconColumn::make('is_disable_in_report')
                    ->label('Raporda Gizli')
                    ->boolean(),

                IconColumn::make('is_sacrifice_type')
                    ->label('Kurban Türü')
                    ->boolean(),

                TextColumn::make('company_id')
                    ->label('Kapsam')
                    ->formatStateUsing(fn ($state) => $state === null ? 'Sistem' : 'Şirket'),
            ])
            ->paginationPageOptions([20, 50, 100])
            ->defaultPaginationPageOption(20)
            ->filters([
                SelectFilter::make('type')
                    ->label('Tür')
                    ->options([
                        'income'  => 'Gelir',
                        'expense' => 'Gider',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),

                Filter::make('system_only')
                    ->label('Sistem Kategorileri')
                    ->query(fn (Builder $query) => $query->whereNull('company_id')),
            ])
            ->actions([
                ViewAction::make()->label('Görüntüle'),
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
            'index'  => Pages\ListSafeTransactionCategories::route('/'),
            'create' => Pages\CreateSafeTransactionCategory::route('/create'),
            'view'   => Pages\ViewSafeTransactionCategory::route('/{record}'),
            'edit'   => Pages\EditSafeTransactionCategory::route('/{record}/edit'),
        ];
    }
}
