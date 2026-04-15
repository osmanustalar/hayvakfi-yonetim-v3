<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanEntryResource\Pages;

use App\Filament\Resources\KurbanEntryResource;
use App\Models\KurbanList;
use App\Models\KurbanSeason;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

class ListKurbanEntries extends ListRecords
{
    protected static string $resource = KurbanEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Excel İndir')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Select::make('season_id')
                        ->label('Sezon')
                        ->options(fn () => KurbanSeason::orderByDesc('year')
                            ->pluck('year', 'id')
                            ->map(fn ($y) => $y . ' Yılı Kurban Sezonu')
                        )
                        ->default(fn () => KurbanSeason::where('is_active', true)->first()?->id)
                        ->required()
                        ->live()
                        ->searchable(),

                    Select::make('list_id')
                        ->label('Liste (Opsiyonel)')
                        ->options(fn (Get $get) => KurbanList::with(['season', 'collector'])
                            ->where('kurban_season_id', $get('season_id') ?? 0)
                            ->get()
                            ->mapWithKeys(fn (KurbanList $l) => [$l->id => $l->getTitle()])
                        )
                        ->placeholder('Tümü')
                        ->searchable(),

                    Select::make('paid')
                        ->label('Ödeme Durumu')
                        ->options([
                            'all' => 'Tümü',
                            '1'   => 'Sadece Ödenenler',
                            '0'   => 'Sadece Ödenmeyenler',
                        ])
                        ->default('all')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->redirect(route('kurban.export', [
                        'season' => $data['season_id'],
                        'list'   => $data['list_id'] ?? null,
                        'paid'   => $data['paid'],
                    ]));
                }),

            CreateAction::make()
                ->label('Yeni Kayıt'),
        ];
    }
}
