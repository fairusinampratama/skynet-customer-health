<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Area;
use App\Models\HealthCheck;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class SlaDowntimeChart extends ChartWidget
{
    protected static ?string $heading = 'Analisis SLA — Rata-rata Waktu Recovery per Daerah (1 Bulan)';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';
    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $oneMonthAgo = now()->subMonth();

        // For each area, calculate average downtime duration
        // Logic: find sequences of "down" checks followed by "up" checks
        // Measure time difference between first down and first up after down = recovery time
        
        $areas = Area::with(['customers' => function ($q) {
            $q->where('is_isolated', false);
        }])->get();

        $labels = [];
        $avgRecoveryMinutes = [];
        $maxRecoveryMinutes = [];
        $totalIncidents = [];
        $slaPercent = [];

        foreach ($areas as $area) {
            $customerIds = $area->customers->pluck('id');
            
            if ($customerIds->isEmpty()) {
                continue;
            }

            // Get all health checks ordered by customer and time
            $checks = HealthCheck::whereIn('customer_id', $customerIds)
                ->where('checked_at', '>=', $oneMonthAgo)
                ->orderBy('customer_id')
                ->orderBy('checked_at')
                ->get();

            if ($checks->isEmpty()) {
                continue;
            }

            // Group by customer
            $byCustomer = $checks->groupBy('customer_id');
            $recoveryTimes = [];
            $incidents = 0;
            $totalDownChecks = 0;
            $totalChecks = $checks->count();

            foreach ($byCustomer as $customerId => $customerChecks) {
                $downStart = null;
                
                foreach ($customerChecks as $check) {
                    if ($check->status === 'down' && $downStart === null) {
                        // Start of a downtime
                        $downStart = $check->checked_at;
                    } elseif (($check->status === 'up' || $check->status === 'unstable') && $downStart !== null) {
                        // Recovery! Calculate duration
                        $duration = $downStart->diffInMinutes($check->checked_at);
                        $recoveryTimes[] = max($duration, 1); // minimum 1 minute
                        $incidents++;
                        $downStart = null;
                    }
                }
                
                // If still down at end of period, count as ongoing (use time until now)
                if ($downStart !== null) {
                    $duration = $downStart->diffInMinutes(now());
                    $recoveryTimes[] = max($duration, 1);
                    $incidents++;
                }
            }

            if (empty($recoveryTimes)) {
                $labels[] = $area->name;
                $avgRecoveryMinutes[] = 0;
                $maxRecoveryMinutes[] = 0;
                $totalIncidents[] = 0;
                $slaPercent[] = 100;
                continue;
            }

            $avg = round(array_sum($recoveryTimes) / count($recoveryTimes));
            $max = max($recoveryTimes);
            $totalDownChecks = $checks->where('status', 'down')->count();
            $sla = $totalChecks > 0 ? round((1 - ($totalDownChecks / $totalChecks)) * 100, 2) : 100;

            $labels[] = $area->name;
            $avgRecoveryMinutes[] = $avg;
            $maxRecoveryMinutes[] = $max;
            $totalIncidents[] = $incidents;
            $slaPercent[] = $sla;
        }

        // Sort by avg recovery time (worst first)
        $combined = collect($labels)->map(fn ($label, $i) => [
            'label' => $label,
            'avg' => $avgRecoveryMinutes[$i],
            'max' => $maxRecoveryMinutes[$i],
            'incidents' => $totalIncidents[$i],
            'sla' => $slaPercent[$i],
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
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) {
                            var label = context.dataset.label || "";
                            var value = context.parsed.y;
                            if (label.includes("menit")) {
                                var h = Math.floor(value / 60);
                                var m = value % 60;
                                return label + ": " + (h > 0 ? h + "j " : "") + m + "m";
                            }
                            return label + ": " + value;
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
                    'position' => 'left',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Waktu Recovery (menit)',
                    ],
                    'grid' => [
                        'color' => 'rgba(255,255,255,0.05)',
                    ],
                ],
                'y1' => [
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Total Insiden',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
