<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Reports\DashboardService;
use Filament\Widgets\ChartWidget;

class CashFlowChartWidget extends ChartWidget
{
    protected ?string $heading = 'Son 12 Ay Nakit Akışı';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $service = new DashboardService();
        $data = $service->getCashFlowData();

        return [
            'datasets' => [
                [
                    'label' => 'Gelir',
                    'data' => $data['income'],
                    'borderColor' => '#22c55e',
                    'backgroundColor' => '#22c55e',
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Gider',
                    'data' => $data['expense'],
                    'borderColor' => '#ef4444',
                    'backgroundColor' => '#ef4444',
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
