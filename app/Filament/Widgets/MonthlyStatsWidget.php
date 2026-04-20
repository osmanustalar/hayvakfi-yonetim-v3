<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Reports\DashboardService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MonthlyStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $service = new DashboardService();
        $stats = $service->getMonthlyStats();

        $thisMonthIncome = (float) $stats['this_month_income'];
        $thisMonthExpense = (float) $stats['this_month_expense'];
        $lastMonthIncome = (float) $stats['last_month_income'];
        $lastMonthExpense = (float) $stats['last_month_expense'];

        $net = $thisMonthIncome - $thisMonthExpense;

        $incomeDescription = $this->calculateChangeDescription($thisMonthIncome, $lastMonthIncome);
        $expenseDescription = $this->calculateChangeDescription($thisMonthExpense, $lastMonthExpense);

        return [
            Stat::make('Bu Ay Gelir', number_format($thisMonthIncome, 2, ',', '.') . ' ₺')
                ->description($incomeDescription)
                ->color('success'),

            Stat::make('Bu Ay Gider', number_format($thisMonthExpense, 2, ',', '.') . ' ₺')
                ->description($expenseDescription)
                ->color('danger'),

            Stat::make('Net Bakiye Değişimi', number_format($net, 2, ',', '.') . ' ₺')
                ->description($net >= 0 ? 'Pozitif' : 'Negatif')
                ->color($net >= 0 ? 'success' : 'danger'),
        ];
    }

    private function calculateChangeDescription(float $current, float $previous): string
    {
        if ($previous == 0) {
            return 'Geçen ay veri yok';
        }

        $change = (($current - $previous) / $previous) * 100;
        $arrow = $change >= 0 ? '▲' : '▼';
        $changeFormatted = number_format(abs($change), 1, ',', '.');

        return "Geçen aya göre {$arrow} %{$changeFormatted}";
    }
}
