<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;

class GangguanPerAreaBarChart extends ChartWidget
{
    protected ?string $heading = 'Top 5 Daerah — Gangguan Terbanyak (7 Hari)';
    protected ?string $description = 'v4';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';
    protected ?string $maxHeight = '350px';
    protected static bool $isLazy = false;

    protected function getData(): array
    {
        $since = now()->subDays(7);

        $data = DB::table('health_checks')
            ->join('customers', 'health_checks.customer_id', '=', 'customers.id')
            ->join('areas', 'customers.area_id', '=', 'areas.id')
            ->where('health_checks.status', 'down')
            ->where('health_checks.checked_at', '>=', $since)
            ->where('customers.is_isolated', false)
            ->whereNotNull('customers.area_id')
            ->groupBy('areas.id', 'areas.name')
            ->select('areas.name as name', DB::raw('COUNT(*) as total_gangguan'))
            ->orderByDesc('total_gangguan')
            ->limit(5)
            ->get();

        if ($data->isEmpty() || $data->sum('total_gangguan') == 0) {
            return [
                'datasets' => [
                    [
                        'label' => 'Total Gangguan',
                        'data' => [0],
                        'backgroundColor' => ['rgba(100,100,100,0.3)'],
                        'borderColor' => ['rgba(100,100,100,0.5)'],
                        'borderWidth' => 1,
                        'borderRadius' => 6,
                    ],
                ],
                'labels' => ['Belum ada data gangguan (7 hari terakhir)'],
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

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return "Total Gangguan: " + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Total Gangguan' },
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { precision: 0 }
                }
            }
        }
        JS);
    }
}
