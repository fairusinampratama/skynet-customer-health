<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;

class SlaDowntimeChart extends ChartWidget
{
    protected ?string $heading = 'Avg Down Intensity per Daerah (7 Hari)';
    protected ?string $description = 'Rata-rata down count/hari per area';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';
    protected ?string $maxHeight = '350px';
    protected static bool $isLazy = false;

    protected function getData(): array
    {
        // Use pre-aggregated daily_stats (713 rows) — INSTANT query
        // Shows avg daily down_count per area over last 7 days
        $data = DB::table('health_check_daily_stats')
            ->join('areas', 'health_check_daily_stats.area_id', '=', 'areas.id')
            ->where('health_check_daily_stats.date', '>=', now()->subDays(7)->toDateString())
            ->where('health_check_daily_stats.down_count', '>', 0)
            ->groupBy('areas.id', 'areas.name')
            ->select('areas.name as name', DB::raw('ROUND(AVG(down_count)) as avg_down'))
            ->orderByDesc('avg_down')
            ->limit(10)
            ->get();

        if ($data->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Avg Down/Hari',
                        'data' => [0],
                        'backgroundColor' => ['rgba(100,100,100,0.3)'],
                        'borderColor' => ['rgba(100,100,100,0.5)'],
                        'borderWidth' => 1,
                        'borderRadius' => 6,
                    ],
                ],
                'labels' => ['Belum ada data (7 hari terakhir)'],
            ];
        }

        $count = $data->count();
        $colors = [];
        $borders = [];
        for ($i = 0; $i < $count; $i++) {
            $ratio = $count > 1 ? $i / ($count - 1) : 0;
            $r = round(239 - ($ratio * 180));
            $g = round(68 + ($ratio * 62));
            $b = round(68 + ($ratio * 188));
            $colors[] = "rgba({$r}, {$g}, {$b}, 0.85)";
            $borders[] = "rgb({$r}, {$g}, {$b})";
        }

        return [
            'datasets' => [
                [
                    'label' => 'Avg Down/Hari',
                    'data' => $data->pluck('avg_down')->toArray(),
                    'backgroundColor' => $colors,
                    'borderColor' => $borders,
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
                            return "Avg Down/Hari: " + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { maxRotation: 45, minRotation: 30 },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Rata-rata Down Count per Hari' },
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: {
                        precision: 0,
                        callback: function(value) { return value.toLocaleString(); }
                    }
                }
            }
        }
        JS);
    }
}
