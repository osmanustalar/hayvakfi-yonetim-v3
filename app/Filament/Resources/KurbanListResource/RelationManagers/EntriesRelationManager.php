<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanListResource\RelationManagers;

use App\Enums\LivestockType;
use App\Models\Contact;
use App\Models\SafeTransactionCategory;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $title = 'Kurban Kayıtları';

    protected static ?string $recordTitleAttribute = 'first_name';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Grid::make(1)
                ->schema([
                    Select::make('contact_id')
                        ->label('Kişi')
                        ->required()
                        ->options(fn () => Contact::query()
                            ->with('region')
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn (Contact $c): array => [
                                $c->id => trim($c->first_name.' '.$c->last_name)
                                    .($c->phone ? ' — '.$c->phone : '')
                                    .($c->region ? ' — '.$c->region->name : ''),
                            ])
                            ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Grid::make(2)->schema([
                                TextInput::make('first_name')
                                    ->label('Ad')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('last_name')
                                    ->label('Soyad')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->maxLength(30)
                                    ->columnSpanFull(),
                            ]),
                        ])
                        ->createOptionUsing(function (array $data) {
                            return Contact::create([
                                'company_id' => session('active_company_id'),
                                'first_name' => $data['first_name'],
                                'last_name' => $data['last_name'],
                                'phone' => $data['phone'] ?? null,
                                'created_user_id' => auth()->id(),
                            ])->id;
                        })
                        ->prefixIcon('heroicon-o-user'),

                    Grid::make(2)->schema([
                        Select::make('livestock_type')
                            ->label('Hayvan Türü (Hisse)')
                            ->required()
                            ->options(collect(LivestockType::cases())->mapWithKeys(fn (LivestockType $t) => [$t->value => $t->label()])->toArray())
                            ->default(fn (?RelationManager $livewire): string => $livewire?->getOwnerRecord()?->season?->default_livestock_type?->value ?? LivestockType::LARGE->value)
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
                    ]),

                    Textarea::make('notes')
                        ->label('Açıklama')
                        ->rows(3),
                ]),
        ]);
    }

    public function table(Table $table): Table
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

                TextColumn::make('contact.phone')
                    ->label('Telefon')
                    ->searchable(),

                TextColumn::make('sacrificeCategory.name')
                    ->label('Kurban Türü'),

                IconColumn::make('is_paid')
                    ->label('Ödendi')
                    ->boolean(),

                TextColumn::make('paid_date')
                    ->label('Ödeme Tarihi')
                    ->date('d.m.Y')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_paid')
                    ->label('Ödeme Durumu')
                    ->placeholder('Tümü')
                    ->trueLabel('Ödenen')
                    ->falseLabel('Ödenmemiş'),

                SelectFilter::make('sacrifice_category_id')
                    ->label('Kurban Türü')
                    ->relationship('sacrificeCategory', 'name')
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Yeni Kayıt')
                    ->slideOver()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = (int) session('active_company_id');
                        $data['created_user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Düzenle')
                    ->slideOver(),
                DeleteAction::make()->label('Sil'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Seçilenleri Sil'),
            ]);
    }
}
