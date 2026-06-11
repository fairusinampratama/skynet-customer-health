<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TotalDowntimePerAreaChart extends ChartWidget
{
    protected ?string $heading = 'Total Downtime per Area';

    protected static ?int $sort = 90;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    protected ?string $maxHeight = '360px';

    protected function getData(): array
    {
        $data = Cache::remember('widget_total_downtime_per_area_current', 60, function () {
            return DB::table('areas')
                ->leftJoin('customers', function ($join) {
                    $join->on('customers.area_id', '=', 'areas.id')
                        ->where('customers.status', 'down')
                        ->where('customers.is_isolated', false);
                })
                ->select('areas.name')
                ->selectRaw('COALESCE(SUM(GREATEST(TIMESTAMPDIFF(MINUTE, customers.updated_at, NOW()), 1)), 0) as downtime_minutes')
                ->groupBy('areas.id', 'areas.name')
                ->orderByDesc('downtime_minutes')
                ->get();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Minutes Downtime',
                    'data' => $data->pluck('downtime_minutes')->map(fn ($value) => (int) $value)->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.82)',
                    'borderColor' => 'rgb(220, 38, 38)',
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
                'legend' => ['display' => true, 'position' => 'top'],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['maxRotation' => 45, 'minRotation' => 20],
                    'title' => ['display' => true, 'text' => 'Area'],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Minutes Downtime'],
                ],
            ],
        ];
    }
}
