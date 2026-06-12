<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $last7Days = Cache::remember('widget_stats_overview_daily_sparkline', 300, function () {
            return DB::table('health_check_daily_stats')
                ->where('date', '>=', now()->subDays(7)->toDateString())
                ->groupBy('date')
                ->orderByDesc('date')
                ->selectRaw('
                    date,
                    SUM(down_count) as down_count,
                    SUM(total_count) as total_count
                ')
                ->limit(7)
                ->get()
                ->reverse()
                ->values();
        });

        $counts = Cache::remember('widget_stats_overview_customer_counts', 30, function () {
            $statusCounts = Customer::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            return [
                'total' => Customer::count(),
                'up' => (int) ($statusCounts['up'] ?? 0),
                'unstable' => (int) ($statusCounts['unstable'] ?? 0),
                'down' => Customer::criticallyDown()->count(),
            ];
        });

        $upChart = $last7Days->map(fn($d) => $d->total_count - $d->down_count)->toArray();
        $downChart = $last7Days->pluck('down_count')->toArray();
        $unstableChart = $last7Days->map(fn($d) => 0)->toArray(); // daily_stats doesn't track unstable separately per day

        return [
            Stat::make('Total Customers', $counts['total']),
            Stat::make('Up', $counts['up'])
                ->description('Customers currently UP')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($upChart)
                ->color('success'),
            Stat::make('Unstable', $counts['unstable'])
                ->description('Customers currently UNSTABLE')
                ->chart($unstableChart)
                ->color('warning'),
            Stat::make('Down', $counts['down'])
                ->description('Customers CRITICALLY DOWN')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart($downChart)
                ->color('danger'),
        ];
    }
}
