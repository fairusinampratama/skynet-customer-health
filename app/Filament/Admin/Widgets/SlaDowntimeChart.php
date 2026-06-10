<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Area;
use App\Models\HealthCheck;
use App\Models\Customer;

class SlaDowntimeChart extends ChartWidget
{
    protected ?string $heading = 'Avg Waktu Recovery per Daerah (1 Bulan)';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';
    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $oneMonthAgo = now()->subMonth();

        $areas = Area::with(['customers' => function ($q) {
            $q->where('is_isolated', false);
        }])->get();

        $labels = [];
        $avgRecoveryMinutes = [];

        foreach ($areas as $area) {
            $customerIds = $area->customers->pluck('id');

            if ($customerIds->isEmpty()) {
                continue;
            }

            $checks = HealthCheck::whereIn('customer_id', $customerIds)
                ->where('checked_at', '>=', $oneMonthAgo)
                ->orderBy('customer_id')
                ->orderBy('checked_at')
                ->get();

            if ($checks->isEmpty()) {
                continue;
            }

            $byCustomer = $checks->groupBy('customer_id');
            $recoveryTimes = [];

            foreach ($byCustomer as $customerId => $customerChecks) {
                $downStart = null;

                foreach ($customerChecks as $check) {
                    if ($check->status === 'down' && $downStart === null) {
                        $downStart = $check->checked_at;
                    } elseif (($check->status === 'up' || $check->status === 'unstable') && $downStart !== null) {
                        $duration = $downStart->diffInMinutes($check->checked_at);
                        $recoveryTimes[] = max($duration, 1);
                        $downStart = null;
                    }
                }

                if ($downStart !== null) {
                    $duration = $downStart->diffInMinutes(now());
                    $recoveryTimes[] = max($duration, 1);
                }
            }

            if (empty($recoveryTimes)) {
                continue;
            }

            $avg = round(array_sum($recoveryTimes) / count($recoveryTimes));
            $labels[] = $area->name;
            $avgRecoveryMinutes[] = $avg;
        }

        // Sort by avg recovery (worst first)
        $combined = collect($labels)->map(fn ($label, $i) => [
            'label' => $label,
            'avg' => $avgRecoveryMinutes[$i],
        ])->sortByDesc('avg')->values();

        // Color gradient: red for worst, blue for best
        $count = $combined->count();
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
                    'data' => $combined->pluck('avg')->toArray(),
                    'backgroundColor' => $colors,
                    'borderColor' => $borders,
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $combined->pluck('label')->toArray(),
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
                        'label' => 'function(context) {
                            var value = context.parsed.y;
                            var h = Math.floor(value / 60);
                            var m = value % 60;
                            return "Avg Recovery: " + (h > 0 ? h + "j " : "") + m + "m";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 30,
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Rata-rata Waktu Recovery (menit)',
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
