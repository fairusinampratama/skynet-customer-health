<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Area;
use App\Models\HealthCheck;
use App\Models\Customer;

class SlaDowntimeChart extends ChartWidget
{
    protected ?string $heading = 'Analisis SLA — Rata-rata Waktu Recovery per Daerah (1 Bulan)';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';
    protected ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $oneMonthAgo = now()->subMonth();
        $areas = Area::with(['customers' => function ($q) {
            $q->where('is_isolated', false);
        }])->get();

        $labels = [];
        $avgRecoveryMinutes = [];
        $maxRecoveryMinutes = [];
        $totalIncidents = [];

        foreach ($areas as $area) {
            $customerIds = $area->customers->pluck('id');
            if ($customerIds->isEmpty()) continue;

            $checks = HealthCheck::whereIn('customer_id', $customerIds)
                ->where('checked_at', '>=', $oneMonthAgo)
                ->orderBy('customer_id')->orderBy('checked_at')->get();

            if ($checks->isEmpty()) continue;

            $byCustomer = $checks->groupBy('customer_id');
            $recoveryTimes = [];
            $incidents = 0;

            foreach ($byCustomer as $customerChecks) {
                $downStart = null;
                foreach ($customerChecks as $check) {
                    if ($check->status === 'down' && $downStart === null) {
                        $downStart = $check->checked_at;
                    } elseif (($check->status === 'up' || $check->status === 'unstable') && $downStart !== null) {
                        $recoveryTimes[] = max($downStart->diffInMinutes($check->checked_at), 1);
                        $incidents++;
                        $downStart = null;
                    }
                }
                if ($downStart !== null) {
                    $recoveryTimes[] = max($downStart->diffInMinutes(now()), 1);
                    $incidents++;
                }
            }

            if (empty($recoveryTimes)) {
                $labels[] = $area->name;
                $avgRecoveryMinutes[] = 0;
                $maxRecoveryMinutes[] = 0;
                $totalIncidents[] = 0;
                continue;
            }

            $labels[] = $area->name;
            $avgRecoveryMinutes[] = round(array_sum($recoveryTimes) / count($recoveryTimes));
            $maxRecoveryMinutes[] = max($recoveryTimes);
            $totalIncidents[] = $incidents;
        }

        $combined = collect($labels)->map(fn ($label, $i) => [
            'label' => $label,
            'avg' => $avgRecoveryMinutes[$i],
            'max' => $maxRecoveryMinutes[$i],
            'incidents' => $totalIncidents[$i],
        ])->sortByDesc('avg')->values();

        return [
            'datasets' => [
                [
                    'label' => 'Rata-rata Recovery (menit)',
                    'data' => $combined->pluck('avg')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.85)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Maks Recovery (menit)',
                    'data' => $combined->pluck('max')->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.6)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Total Insiden',
                    'data' => $combined->pluck('incidents')->toArray(),
                    'backgroundColor' => 'rgba(168, 85, 247, 0.6)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                    'yAxisID' => 'y1',
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
                'legend' => ['display' => true, 'position' => 'top'],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'x' => [
                    'ticks' => ['maxRotation' => 45, 'minRotation' => 30],
                    'grid' => ['display' => false],
                ],
                'y' => [
                    'position' => 'left',
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Waktu Recovery (menit)'],
                    'grid' => ['color' => 'rgba(255,255,255,0.05)'],
                ],
                'y1' => [
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Total Insiden'],
                    'grid' => ['drawOnChartArea' => false],
                ],
            ],
        ];
    }
}
