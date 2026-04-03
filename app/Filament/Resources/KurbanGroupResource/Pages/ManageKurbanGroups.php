<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanGroupResource\Pages;

use App\Filament\Resources\KurbanGroupResource;
use App\Models\KurbanSeason;
use App\Repositories\KurbanGroupRepository;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;

class ManageKurbanGroups extends ManageRecords
{
    protected static string $resource = KurbanGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Yazdır')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->form([
                    Select::make('season_id')
                        ->label('Sezon')
                        ->options(fn () => KurbanSeason::orderByDesc('year')->pluck('year', 'id')->map(fn ($y) => $y . ' Yılı Kurban Sezonu'))
                        ->default(fn () => KurbanSeason::where('is_active', true)->first()?->id) // Aktif sezonu varsayılan seç
                        ->required()
                        ->searchable(),
                    TextInput::make('start')
                        ->label('Başlangıç Grup No')
                        ->numeric()
                        ->placeholder('Örn: 101'),
                    TextInput::make('end')
                        ->label('Bitiş Grup No')
                        ->numeric()
                        ->placeholder('Örn: 150'),
                ])
                ->action(function (array $data) {
                    return redirect()->to(route('kurban.print', [
                        'season' => $data['season_id'],
                        'start' => $data['start'] ?? null,
                        'end' => $data['end'] ?? null,
                    ]));
                }),

            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['company_id'] = session('active_company_id');
                    $data['created_user_id'] = auth()->id();

                    // Sezon için bir sonraki grup numarasını otomatik al
                    $repository = app(KurbanGroupRepository::class);
                    $data['group_no'] = $repository->nextGroupNo(
                        (int) $data['company_id'],
                        (int) $data['kurban_season_id']
                    );

                    return $data;
                }),
        ];
    }
}
