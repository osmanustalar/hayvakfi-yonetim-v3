<?php

declare(strict_types=1);

namespace App\Filament\Resources\KurbanGroupResource\Pages;

use App\Filament\Resources\KurbanGroupResource;
use App\Repositories\KurbanGroupRepository;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageKurbanGroups extends ManageRecords
{
    protected static string $resource = KurbanGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
