<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\TransactionType;
use App\Models\SafeTransaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $companyId = session('active_company_id');

        return $table
            ->query(
                SafeTransaction::query()
                    ->where('company_id', $companyId)
                    ->with(['safe', 'items.category'])
                    ->orderByDesc('process_date')
                    ->orderByDesc('created_at')
                    ->limit(10)
            )
            ->heading('Son İşlemler')
            ->columns([
                TextColumn::make('process_date')
                    ->label('İşlem Tarihi')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('safe.name')
                    ->label('Kasa')
                    ->sortable(),

                TextColumn::make('category_name')
                    ->label('Kategori')
                    ->getStateUsing(function ($record) {
                        return $record->items->first()?->category?->name ?? '-';
                    }),

                TextColumn::make('total_amount')
                    ->label('Tutar')
                    ->formatStateUsing(function ($record) {
                        $amount = number_format((float) $record->total_amount, 2, ',', '.');
                        return $amount . ' ₺';
                    })
                    ->color(fn ($record) => $record->type === TransactionType::INCOME ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state === TransactionType::INCOME ? 'success' : 'danger'),
            ])
            ->paginated(false);
    }
}
