<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SlaDowntimeChart extends ChartWidget
{
    protected ?string $heading = 'Avg Waktu Recovery per Daerah (7 Hari)';
    protected ?string $description = 'v4';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';
    protected ?string $maxHeight = '350px';
    protected static bool $isLazy = false;

    protected function getData(): array
    {
        $since = now()->subDays(7);

        // Step 1: Get all down events with their next status (using window functions)
        // This query works on MySQL 8+ and PostgreSQL (both support window functions)
        $downEvents = DB::select("
            SELECT 
                a.id AS area_id,
                a.name AS area_name,
                hc.customer_id,
                hc.checked_at AS down_at,
                LEAD(hc.status) OVER (PARTITION BY hc.customer_id ORDER BY hc.checked_at) AS next_status,
                LEAD(hc.checked_at) OVER (PARTITION BY hc.customer_id ORDER BY hc.checked_at) AS next_checked_at
            FROM health_checks hc
            JOIN customers c ON hc.customer_id = c.id
            JOIN areas a ON c.area_id = a.id
            WHERE hc.checked_at >= ?
              AND c.is_isolated = 0
              AND c.area_id IS NOT NULL
              AND hc.status = 'down'
        ", [$since->toDateTimeString()]);

        if (empty($downEvents)) {
            return $this->emptyDataset('Belum ada data recovery (7 hari terakhir)');
        }

        // Step 2: Calculate recovery time per area (date math in PHP for DB compatibility)
        $areaTotals = [];

        foreach ($downEvents as $event) {
            $downAt = Carbon::parse($event->down_at);

            if (in_array($event->next_status, ['up', 'unstable']) && $event->next_checked_at) {
                $nextAt = Carbon::parse($event->next_checked_at);
                $minutes = max($downAt->diffInMinutes($nextAt), 1);
            } else {
                // Still down or no recovery found
                $minutes = max($downAt->diffInMinutes(now()), 1);
            }

            $areaId = $event->area_id;
            if (!isset($areaTotals[$areaId])) {
                $areaTotals[$areaId] = ['name' => $event->area_name, 'sum' => 0, 'count' => 0];
            }
            $areaTotals[$areaId]['sum'] += $minutes;
            $areaTotals[$areaId]['count']++;
        }

        $areaRecovery = [];
        foreach ($areaTotals as $data) {
            $areaRecovery[] = [
                'name' => $data['name'],
                'avg' => round($data['sum'] / $data['count']),
            ];
        }

        if (empty($areaRecovery)) {
            return $this->emptyDataset('Belum ada data recovery (7 hari terakhir)');
        }

        // Sort worst first
        usort($areaRecovery, fn($a, $b) => $b['avg'] <=> $a['avg']);

        $count = count($areaRecovery);
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
                    'label' => 'Rata-rata Recovery (menit)',
                    'data' => array_column($areaRecovery, 'avg'),
                    'backgroundColor' => $colors,
                    'borderColor' => $borders,
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => array_column($areaRecovery, 'name'),
        ];
    }

    private function emptyDataset(string $msg): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Rata-rata Recovery (menit)',
                    'data' => [0],
                    'backgroundColor' => ['rgba(100,100,100,0.3)'],
                    'borderColor' => ['rgba(100,100,100,0.5)'],
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => [$msg],
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
                            var value = context.parsed.y;
                            var h = Math.floor(value / 60);
                            var m = value % 60;
                            return "Avg Recovery: " + (h > 0 ? h + "j " : "") + m + "m";
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
                    title: { display: true, text: 'Rata-rata Waktu Recovery (menit)' },
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { precision: 0 }
                }
            }
        }
        JS);
    }
}
