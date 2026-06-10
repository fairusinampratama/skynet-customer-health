<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Area;
use App\Models\HealthCheck;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class SlaDowntimeChart extends ChartWidget
{
    protected ?string $heading = 'Avg Waktu Recovery per Daerah (7 Hari)';
    protected ?string $description = 'v3';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';
    protected ?string $maxHeight = '350px';

    protected function getData(): array
    {
        // Use 7 days since HealthCheck prunes records older than 7 days
        $since = now()->subDays(7);

        // Get all non-isolated customers with their areas
        $customers = Customer::where('is_isolated', false)
            ->whereNotNull('area_id')
            ->with('area')
            ->get();

        if ($customers->isEmpty()) {
            return $this->emptyDataset('Belum ada customer non-isolated');
        }

        $areaRecovery = [];

        foreach ($customers->groupBy('area_id') as $areaId => $areaCustomers) {
            $areaName = $areaCustomers->first()->area?->name ?? 'Unknown';
            $recoveryTimes = [];

            foreach ($areaCustomers as $customer) {
                $checks = HealthCheck::where('customer_id', $customer->id)
                    ->where('checked_at', '>=', $since)
                    ->orderBy('checked_at')
                    ->get();

                if ($checks->count() < 2) {
                    continue;
                }

                $downStart = null;
                foreach ($checks as $check) {
                    if ($check->status === 'down' && $downStart === null) {
                        $downStart = $check->checked_at;
                    } elseif (in_array($check->status, ['up', 'unstable']) && $downStart !== null) {
                        $duration = $downStart->diffInMinutes($check->checked_at);
                        $recoveryTimes[] = max($duration, 1);
                        $downStart = null;
                    }
                }

                // Still down — count until now
                if ($downStart !== null) {
                    $duration = $downStart->diffInMinutes(now());
                    $recoveryTimes[] = max($duration, 1);
                }
            }

            if (!empty($recoveryTimes)) {
                $areaRecovery[] = [
                    'name' => $areaName,
                    'avg' => round(array_sum($recoveryTimes) / count($recoveryTimes)),
                ];
            }
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
