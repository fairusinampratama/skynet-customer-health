<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Area;
use App\Models\HealthCheck;
use App\Models\Customer;

class SlaStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 5;
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $oneMonthAgo = now()->subMonth();

        $totalChecks = HealthCheck::where('checked_at', '>=', $oneMonthAgo)->count();
        $downChecks = HealthCheck::where('checked_at', '>=', $oneMonthAgo)
            ->where('status', 'down')->count();
        $overallSla = $totalChecks > 0 ? round((1 - ($downChecks / $totalChecks)) * 100, 2) : 100;

        $worstArea = Area::query()
            ->select('areas.name')
            ->selectRaw("COUNT(CASE WHEN health_checks.status = 'down' THEN 1 END) as down_count")
            ->join('customers', 'customers.area_id', '=', 'areas.id')
            ->join('health_checks', 'health_checks.customer_id', '=', 'customers.id')
            ->where('health_checks.checked_at', '>=', $oneMonthAgo)
            ->where('customers.is_isolated', false)
            ->groupBy('areas.id', 'areas.name')
            ->orderByDesc('down_count')
            ->first();

        $totalDownCustomers = Customer::where('status', 'down')
            ->where('is_isolated', false)
            ->where('updated_at', '>=', $oneMonthAgo)
            ->count();

        $avgRecovery = $this->getAvgRecoveryTime($oneMonthAgo);

        return [
            Stat::make('SLA Bulan Ini', "{$overallSla}%")
                ->description($overallSla >= 99.5 ? 'Target tercapai' : 'Di bawah target 99.5%')
                ->descriptionIcon($overallSla >= 99.5 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($overallSla >= 99.5 ? 'success' : ($overallSla >= 98 ? 'warning' : 'danger')),

            Stat::make('Daerah Terburuk', $worstArea?->name ?? '-')
                ->description($worstArea ? "{$worstArea->down_count} gangguan" : 'Tidak ada data')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color($worstArea && $worstArea->down_count > 100 ? 'danger' : 'warning'),

            Stat::make('Rata-rata Recovery', $avgRecovery)
                ->description('Waktu downtime ke uptime')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Customer Down Saat Ini', $totalDownCustomers)
                ->description('Dari total ' . Customer::where('is_isolated', false)->count() . ' customer')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($totalDownCustomers > 0 ? 'danger' : 'success'),
        ];
    }

    private function getAvgRecoveryTime($since): string
    {
        $areas = Area::with(['customers' => function ($q) {
            $q->where('is_isolated', false);
        }])->get();

        $allRecoveryMinutes = [];

        foreach ($areas as $area) {
            $customerIds = $area->customers->pluck('id');
            if ($customerIds->isEmpty()) continue;

            $checks = HealthCheck::whereIn('customer_id', $customerIds)
                ->where('checked_at', '>=', $since)
                ->orderBy('customer_id')->orderBy('checked_at')->get();

            $byCustomer = $checks->groupBy('customer_id');

            foreach ($byCustomer as $customerChecks) {
                $downStart = null;
                foreach ($customerChecks as $check) {
                    if ($check->status === 'down' && $downStart === null) {
                        $downStart = $check->checked_at;
                    } elseif (($check->status === 'up' || $check->status === 'unstable') && $downStart !== null) {
                        $allRecoveryMinutes[] = max($downStart->diffInMinutes($check->checked_at), 1);
                        $downStart = null;
                    }
                }
                if ($downStart !== null) {
                    $allRecoveryMinutes[] = max($downStart->diffInMinutes(now()), 1);
                }
            }
        }

        if (empty($allRecoveryMinutes)) return '0m';

        $avg = round(array_sum($allRecoveryMinutes) / count($allRecoveryMinutes));
        $h = floor($avg / 60);
        $m = $avg % 60;
        return ($h > 0 ? "{$h}j " : '') . "{$m}m";
    }
}
