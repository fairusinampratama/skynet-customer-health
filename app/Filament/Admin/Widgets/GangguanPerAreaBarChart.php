<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Area;
use App\Models\HealthCheck;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class GangguanPerAreaBarChart extends ChartWidget
{
    protected static ?string $heading = 'Gangguan Terbanyak per Daerah (1 Bulan)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';
    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $oneMonthAgo = now()->subMonth();

        // Count DOWN health checks per area in the last month
        $data = Area::query()
            ->select('areas.name')
            ->selectRaw('COUNT(CASE WHEN health_checks.status = \'down\' THEN 1 END) as down_count')
            ->selectRaw('COUNT(CASE WHEN health_checks.status = \'unstable\' THEN 1 END) as unstable_count')
            ->join('customers', 'customers.area_id', '=', 'areas.id')
            ->join('health_checks', 'health_checks.customer_id', '=', 'customers.id')
            ->where('health_checks.checked_at', '>=', $oneMonthAgo)
            ->where('customers.is_isolated', false)
            ->groupBy('areas.id', 'areas.name')
            ->orderByDesc('down_count')
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
                    'label' => 'Down',
                    'data' => $data->pluck('down_count')->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.85)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Unstable',
                    'data' => $data->pluck('unstable_count')->toArray(),
                    'backgroundColor' => 'rgba(234, 179, 8, 0.85)',
                    'borderColor' => 'rgb(234, 179, 8)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
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
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 30,
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Jumlah Gangguan',
                    ],
                    'grid' => [
                        'color' => 'rgba(255,255,255,0.05)',
                    ],
                ],
            ],
        ];
    }
}
