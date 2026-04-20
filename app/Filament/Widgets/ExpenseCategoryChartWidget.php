<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Reports\DashboardService;
use Filament\Widgets\ChartWidget;

class ExpenseCategoryChartWidget extends ChartWidget
{
    protected ?string $heading = 'Bu Ay Gider Dağılımı';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $service = new DashboardService();
        $data = $service->getExpenseCategoryData();

        if (empty($data['labels'])) {
            return [
                'datasets' => [
                    [
                        'data' => [],
                    ],
                ],
                'labels' => [],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $data['data'],
                    'backgroundColor' => $data['colors'],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getDescription(): ?string
    {
        $service = new DashboardService();
        $data = $service->getExpenseCategoryData();

        if (empty($data['labels'])) {
            return 'Bu ay gider kaydı bulunamadı';
        }

        return null;
    }
}
