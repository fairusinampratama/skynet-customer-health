<?php

namespace App\Filament\Admin\Widgets;

use Carbon\CarbonImmutable;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TotalDowntimePerAreaChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Total Downtime per Area';

    protected static ?int $sort = 90;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    protected ?string $maxHeight = '360px';

    protected function getData(): array
    {
        [$startDate, $endDate] = $this->getMonthRange();
        $areaId = filled($this->pageFilters['areaId'] ?? null)
            ? (int) $this->pageFilters['areaId']
            : null;

        $cacheKey = sprintf(
            'widget_total_downtime_per_area_%s_%s_%s',
            $startDate->toDateString(),
            $endDate->toDateString(),
            $areaId ?: 'all',
        );

        $data = Cache::remember($cacheKey, 60, function () use ($startDate, $endDate, $areaId) {
            return DB::table('areas')
                ->when($areaId, fn ($query) => $query->where('areas.id', $areaId))
                ->leftJoin('customers', function ($join) {
                    $join->on('customers.area_id', '=', 'areas.id')
                        ->where('customers.is_isolated', false);
                })
                ->leftJoin('health_checks', function ($join) use ($startDate, $endDate) {
                    $join->on('health_checks.customer_id', '=', 'customers.id')
                        ->where('health_checks.status', 'down')
                        ->whereBetween('health_checks.checked_at', [$startDate, $endDate]);
                })
                ->select('areas.name')
                ->selectRaw('COUNT(health_checks.id) as downtime_minutes')
                ->groupBy('areas.id', 'areas.name')
                ->orderByDesc('downtime_minutes')
                ->orderBy('areas.name')
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

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            layout: {
                padding: {
                    top: 28
                }
            },
            animation: {
                onComplete: ({ chart }) => {
                    const { ctx } = chart;
                    ctx.save();
                    ctx.font = '600 12px Inter, system-ui, sans-serif';
                    ctx.fillStyle = '#374151';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';

                    chart.data.datasets.forEach((dataset, datasetIndex) => {
                        const meta = chart.getDatasetMeta(datasetIndex);

                        meta.data.forEach((bar, index) => {
                            const value = dataset.data[index] ?? 0;
                            ctx.fillText(value + 'm', bar.x, bar.y - 6);
                        });
                    });

                    ctx.restore();
                }
            },
            plugins: {
                legend: { display: true, position: 'top' },
                tooltip: { mode: 'index', intersect: false },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 45, minRotation: 20 },
                    title: { display: true, text: 'Area' },
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Minutes Downtime' },
                },
            },
        }
        JS);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function getMonthRange(): array
    {
        $month = $this->pageFilters['month'] ?? now()->format('Y-m');

        if (! is_string($month) || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        try {
            $startDate = CarbonImmutable::createFromFormat('Y-m-d H:i:s', "{$month}-01 00:00:00")->startOfMonth();
        } catch (\Throwable) {
            $startDate = CarbonImmutable::now()->startOfMonth();
        }

        return [$startDate, $startDate->endOfMonth()];
    }
}
