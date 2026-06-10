<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Area;
use App\Models\HealthCheck;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SlaStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 5;
    protected ?string $pollingInterval = '60s';
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $since = now()->subDays(7); // Match Prunable retention

        $totalChecks = HealthCheck::where('checked_at', '>=', $since)->count();
        $downChecks = HealthCheck::where('checked_at', '>=', $since)
            ->where('status', 'down')->count();
        $overallSla = $totalChecks > 0 ? round((1 - ($downChecks / $totalChecks)) * 100, 2) : 100;

        $worstArea = Area::query()
            ->select('areas.name')
            ->selectRaw("COUNT(CASE WHEN health_checks.status = 'down' THEN 1 END) as down_count")
            ->join('customers', 'customers.area_id', '=', 'areas.id')
            ->join('health_checks', 'health_checks.customer_id', '=', 'customers.id')
            ->where('health_checks.checked_at', '>=', $since)
            ->where('customers.is_isolated', false)
            ->groupBy('areas.id', 'areas.name')
            ->orderByDesc('down_count')
            ->first();

        $totalDownCustomers = Customer::where('status', 'down')
            ->where('is_isolated', false)
            ->where('updated_at', '>=', $since)
            ->count();

        $avgRecovery = $this->getAvgRecoveryTime($since);

        return [
            Stat::make('SLA (7 Hari)', "{$overallSla}%")
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
        try {
            // Use window functions for efficient down→up transition detection
            $downEvents = DB::select("
                SELECT 
                    hc.customer_id,
                    hc.checked_at AS down_at,
                    LEAD(hc.status) OVER (PARTITION BY hc.customer_id ORDER BY hc.checked_at) AS next_status,
                    LEAD(hc.checked_at) OVER (PARTITION BY hc.customer_id ORDER BY hc.checked_at) AS next_checked_at
                FROM health_checks hc
                JOIN customers c ON hc.customer_id = c.id
                WHERE hc.checked_at >= ?
                  AND c.is_isolated = 0
                  AND hc.status = 'down'
            ", [$since->toDateTimeString()]);

            if (empty($downEvents)) return '0m';

            $totalMinutes = 0;
            $count = 0;

            foreach ($downEvents as $event) {
                $downAt = Carbon::parse($event->down_at);

                if (in_array($event->next_status, ['up', 'unstable']) && $event->next_checked_at) {
                    $nextAt = Carbon::parse($event->next_checked_at);
                    $totalMinutes += max($downAt->diffInMinutes($nextAt), 1);
                } else {
                    $totalMinutes += max($downAt->diffInMinutes(now()), 1);
                }
                $count++;
            }

            if ($count === 0) return '0m';

            $avg = round($totalMinutes / $count);
        } catch (\Exception $e) {
            return '0m';
        }

        $h = floor($avg / 60);
        $m = $avg % 60;
        return ($h > 0 ? "{$h}j " : '') . "{$m}m";
    }
}
