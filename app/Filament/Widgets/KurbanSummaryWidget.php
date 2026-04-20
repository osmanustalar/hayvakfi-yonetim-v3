<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\KurbanSeason;
use App\Services\Reports\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KurbanSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        $companyId = session('active_company_id');

        if (!$companyId) {
            return false;
        }

        $activeSeason = KurbanSeason::where('company_id', $companyId)
            ->where('is_active', true)
            ->exists();

        return $activeSeason;
    }

    protected function getStats(): array
    {
        $service = new DashboardService();
        $summary = $service->getKurbanSummary();

        if (!$summary) {
            return [];
        }

        return [
            Stat::make('Toplam Kayıt', (string) $summary['total'])
                ->description($summary['season_name'])
                ->color('info'),

            Stat::make('Ödenen', (string) $summary['paid'])
                ->description($summary['season_name'])
                ->color('success'),

            Stat::make('Bekleyen', (string) $summary['pending'])
                ->description($summary['season_name'])
                ->color('warning'),
        ];
    }
}
