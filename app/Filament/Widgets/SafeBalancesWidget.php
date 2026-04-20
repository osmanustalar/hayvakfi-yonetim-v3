<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Reports\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SafeBalancesWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $service = new DashboardService();
        $safes = $service->getSafeBalances();

        $stats = [];

        foreach ($safes as $safe) {
            $balance = (float) $safe->balance;
            $formattedBalance = number_format($balance, 2, ',', '.') . ' ' . $safe->currency->symbol;

            $description = $safe->safeGroup->name;
            if ($safe->safeGroup->is_api_integration) {
                $description .= ' (Banka)';
            }

            $color = 'gray';
            if ($balance > 0) {
                $color = 'success';
            } elseif ($balance < 0) {
                $color = 'danger';
            }

            $stats[] = Stat::make($safe->name, $formattedBalance)
                ->description($description)
                ->color($color);
        }

        return $stats;
    }
}
