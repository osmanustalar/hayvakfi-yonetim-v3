<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\OperationType;
use App\Enums\TransactionType;
use App\Filament\Resources\SafeTransactionResource\Pages;
use App\Models\Currency;
use App\Models\Safe;
use App\Models\SafeGroup;
use App\Models\SafeTransaction;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SafeTransactionResource extends Resource
{
    protected static ?string $model = SafeTransaction::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Kasa';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'İşlemler';

    protected static ?string $modelLabel = 'Kasa İşlemi';

    protected static ?string $pluralModelLabel = 'Kasa İşlemleri';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->with('items.category', 'safe', 'currency', 'referenceUser');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('process_date')
                    ->label('İşlem Tarihi')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('safe.name')
                    ->label('Kasa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('items.category.name')
                    ->label('Kategori')
                    ->formatStateUsing(function (SafeTransaction $record): string {
                        if ($record->items->isEmpty()) {
                            return '—';
                        }

                        return $record->items
                            ->map(fn ($item) => $item->category?->name ?? '—')
                            ->implode(', ');
                    }),

                TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state instanceof TransactionType && $state === TransactionType::INCOME  => 'success',
                        $state instanceof TransactionType && $state === TransactionType::EXPENSE => 'danger',
                        (string) $state === 'income'  => 'success',
                        (string) $state === 'expense' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => $state instanceof TransactionType
                        ? $state->label()
                        : (string) $state),

                TextColumn::make('operation_type')
                    ->label('Tip')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state instanceof OperationType && $state === OperationType::TRANSFER => 'info',
                        $state instanceof OperationType && $state === OperationType::EXCHANGE => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => $state instanceof OperationType
                        ? $state->label()
                        : ($state !== null ? (string) $state : '—')),

                TextColumn::make('total_amount')
                    ->label('Tutar')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('currency.symbol')
                    ->label('Para Birimi')
                    ->alignCenter(),

                TextColumn::make('referenceUser.name')
                    ->label('İşlemi Yapan')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('balance_after_created')
                    ->label('Sonraki Bakiye')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_show')
                    ->label('Raporda')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginationPageOptions([20, 50, 100])
            ->defaultPaginationPageOption(20)
            ->defaultSort('process_date', 'desc')
            ->filters([
                Filter::make('process_date')
                    ->label('İşlem Tarihi')
                    ->form([
                        DatePicker::make('from_date')
                            ->label('Başlangıç Tarihi')
                            ->displayFormat('d.m.Y'),
                        DatePicker::make('to_date')
                            ->label('Bitiş Tarihi')
                            ->displayFormat('d.m.Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('process_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('process_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from_date'] ?? null) {
                            $indicators['from_date'] = 'Başlangıç: ' . date('d.m.Y', strtotime($data['from_date']));
                        }

                        if ($data['to_date'] ?? null) {
                            $indicators['to_date'] = 'Bitiş: ' . date('d.m.Y', strtotime($data['to_date']));
                        }

                        return $indicators;
                    }),

                SelectFilter::make('safe_id')
                    ->label('Kasa')
                    ->options(fn (): array => Safe::query()->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('safe_group_id')
                    ->label('Kasa Grubu')
                    ->options(fn (): array => SafeGroup::query()->pluck('name', 'id')->toArray())
                    ->query(fn (Builder $query, array $data): Builder =>
                        $query->when(
                            $data['value'] ?? null,
                            fn (Builder $q): Builder => $q->whereHas('safe', fn (Builder $subQ) => $subQ->where('safe_group_id', $data['value']))
                        )
                    )
                    ->searchable(),

                SelectFilter::make('currency_id')
                    ->label('Para Birimi')
                    ->options(fn (): array => Currency::query()->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('reference_user_id')
                    ->label('İşlemi Yapan')
                    ->options(fn (): array => User::query()->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('type')
                    ->label('İşlem Türü')
                    ->options([
                        'income'  => 'Giriş',
                        'expense' => 'Çıkış',
                    ]),

                SelectFilter::make('operation_type')
                    ->label('Operasyon Tipi')
                    ->options([
                        'exchange' => 'Döviz İşlemi',
                        'transfer' => 'Hesaplar Arası Transfer',
                    ]),

                Filter::make('amount_range')
                    ->label('Tutar Aralığı')
                    ->form([
                        TextInput::make('min_amount')
                            ->label('Minimum Tutar')
                            ->numeric(),
                        TextInput::make('max_amount')
                            ->label('Maksimum Tutar')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'] ?? null,
                                fn (Builder $q, string $amount): Builder => $q->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'] ?? null,
                                fn (Builder $q, string $amount): Builder => $q->where('total_amount', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['min_amount'] ?? null) {
                            $indicators['min_amount'] = 'Min: ' . $data['min_amount'];
                        }

                        if ($data['max_amount'] ?? null) {
                            $indicators['max_amount'] = 'Max: ' . $data['max_amount'];
                        }

                        return $indicators;
                    }),

            ])
            ->filtersFormColumns(2)
            ->actions([
                Action::make('edit')
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil')
                    ->url(function (SafeTransaction $record): ?string {
                        $type          = $record->type instanceof TransactionType
                            ? $record->type->value
                            : (string) $record->type;
                        $operationType = $record->operation_type instanceof OperationType
                            ? $record->operation_type->value
                            : (string) $record->operation_type;

                        if ($operationType === 'exchange') {
                            // Her zaman expense (kaynak) kaydı üzerinden düzenleme
                            if ($type === 'expense') {
                                return static::getUrl('edit-exchange', ['record' => $record->id]);
                            }

                            // Income (hedef) ise kaynak transaction'a yönlendir
                            if ($record->targetTransaction !== null) {
                                return static::getUrl('edit-exchange', ['record' => $record->targetTransaction->id]);
                            }

                            return null;
                        }

                        if ($operationType === 'transfer') {
                            if ($type === 'expense') {
                                return static::getUrl('edit-transfer', ['record' => $record->id]);
                            }

                            if ($record->targetTransaction !== null) {
                                return static::getUrl('edit-transfer', ['record' => $record->targetTransaction->id]);
                            }

                            return null;
                        }

                        if ($type === 'income') {
                            return static::getUrl('edit-income', ['record' => $record->id]);
                        }

                        return static::getUrl('edit-expense', ['record' => $record->id]);
                    }),

                DeleteAction::make()
                    ->label('Sil')
                    ->before(function (SafeTransaction $record): void {
                        // İlişkili transfer/exchange kaydını da sil ve bakiyeleri düzelt
                        $type = $record->type instanceof TransactionType
                            ? $record->type->value
                            : (string) $record->type;

                        $safe = $record->safe;

                        if ($safe !== null) {
                            if ($type === 'income') {
                                $safe->decrement('balance', (float) $record->total_amount);
                            } else {
                                $safe->increment('balance', (float) $record->total_amount);
                            }
                        }

                        $record->items()->delete();

                        if ($record->targetTransaction !== null) {
                            $targetSafe = $record->targetSafe;

                            if ($targetSafe !== null) {
                                $targetType = $record->targetTransaction->type instanceof TransactionType
                                    ? $record->targetTransaction->type->value
                                    : (string) $record->targetTransaction->type;

                                if ($targetType === 'income') {
                                    $targetSafe->decrement('balance', (float) $record->targetTransaction->total_amount);
                                } else {
                                    $targetSafe->increment('balance', (float) $record->targetTransaction->total_amount);
                                }
                            }

                            $record->targetTransaction->items()->delete();
                            $record->targetTransaction->delete();
                        }
                    }),
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
            'index'         => Pages\ListSafeTransactions::route('/'),
            'create-income'   => Pages\CreateIncomeSafeTransaction::route('/create/income/{safe_id}'),
            'create-expense'  => Pages\CreateExpenseSafeTransaction::route('/create/expense/{safe_id}'),
            'create-exchange' => Pages\CreateExchangeSafeTransaction::route('/create/exchange/{safe_id}'),
            'create-transfer' => Pages\CreateTransferSafeTransaction::route('/create/transfer/{safe_id}'),
            'edit-income'     => Pages\EditIncomeSafeTransaction::route('/{record}/edit/income'),
            'edit-expense'    => Pages\EditExpenseSafeTransaction::route('/{record}/edit/expense'),
            'edit-exchange'   => Pages\EditExchangeSafeTransaction::route('/{record}/edit/exchange'),
            'edit-transfer'   => Pages\EditTransferSafeTransaction::route('/{record}/edit/transfer'),
        ];
    }
}
