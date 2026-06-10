<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Area;
use App\Models\HealthCheck;
use App\Models\Customer;

class GangguanPerAreaBarChart extends ChartWidget
{
    protected ?string $heading = 'Top 5 Daerah — Gangguan Terbanyak (1 Bulan)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';
    protected ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $oneMonthAgo = now()->subMonth();

        $data = Area::query()
            ->select('areas.name')
            ->selectRaw('COUNT(CASE WHEN health_checks.status = \'down\' THEN 1 END) as total_gangguan')
            ->join('customers', 'customers.area_id', '=', 'areas.id')
            ->join('health_checks', 'health_checks.customer_id', '=', 'customers.id')
            ->where('health_checks.checked_at', '>=', $oneMonthAgo)
            ->where('customers.is_isolated', false)
            ->groupBy('areas.id', 'areas.name')
            ->orderByDesc('total_gangguan')
            ->limit(5)
            ->get();

        if ($data->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Gangguan',
                    'data' => $data->pluck('total_gangguan')->toArray(),
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.9)',
                        'rgba(249, 115, 22, 0.85)',
                        'rgba(234, 179, 8, 0.8)',
                        'rgba(59, 130, 246, 0.75)',
                        'rgba(168, 85, 247, 0.7)',
                    ],
                    'borderColor' => [
                        'rgb(239, 68, 68)',
                        'rgb(249, 115, 22)',
                        'rgb(234, 179, 8)',
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                    ],
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "Total Gangguan: " + context.parsed.y; }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Total Gangguan',
                    ],
                    'grid' => [
                        'color' => 'rgba(255,255,255,0.05)',
                    ],
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}
