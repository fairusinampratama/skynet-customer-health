<?php

namespace App\Filament\Admin\Widgets;

use Carbon\CarbonImmutable;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BackboneDowntimeChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Downtime Backbone';

    protected static ?int $sort = 91;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    protected ?string $maxHeight = '360px';

    protected function getData(): array
    {
        $labels = ['IIX', 'JKTIX', 'TLN', 'Data Utama', 'THC', 'RBN'];
        [$startDate, $endDate] = $this->getMonthRange();

        $cacheKey = sprintf(
            'widget_backbone_downtime_%s_%s',
            $startDate->toDateString(),
            $endDate->toDateString(),
        );

        $data = Cache::remember($cacheKey, 60, function () use ($startDate, $endDate) {
            return DB::table('servers')
                ->join('server_health_checks', 'server_health_checks.server_id', '=', 'servers.id')
                ->where('server_health_checks.status', 'down')
                ->whereBetween('server_health_checks.checked_at', [$startDate, $endDate])
                ->where(function ($query) {
                    $query->whereRaw('UPPER(servers.name) LIKE ?', ['%IIX%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%JKTIX%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%TLN%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%DATAUTAMA%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%DATA UTAMA%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%THC%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%RBN%']);
                })
                ->selectRaw("
                    CASE
                        WHEN UPPER(servers.name) LIKE '%JKTIX%' THEN 'JKTIX'
                        WHEN UPPER(servers.name) LIKE '%IIX%' THEN 'IIX'
                        WHEN UPPER(servers.name) LIKE '%TLN%' THEN 'TLN'
                        WHEN UPPER(servers.name) LIKE '%DATAUTAMA%' OR UPPER(servers.name) LIKE '%DATA UTAMA%' THEN 'Data Utama'
                        WHEN UPPER(servers.name) LIKE '%THC%' THEN 'THC'
                        WHEN UPPER(servers.name) LIKE '%RBN%' THEN 'RBN'
                    END as backbone
                ")
                ->selectRaw('COUNT(server_health_checks.id) as downtime_minutes')
                ->groupBy('backbone')
                ->pluck('downtime_minutes', 'backbone');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Minutes Downtime',
                    'data' => collect($labels)->map(fn ($label) => (int) ($data[$label] ?? 0))->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.82)',
                    'borderColor' => 'rgb(37, 99, 235)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
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
                    title: { display: true, text: 'Backbone' },
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
