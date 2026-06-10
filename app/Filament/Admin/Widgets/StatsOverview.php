<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        // Use daily_stats for sparkline charts (instant vs 49M row scan)
        $last7Days = DB::table('health_check_daily_stats')
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

        $upChart = $last7Days->map(fn($d) => $d->total_count - $d->down_count)->toArray();
        $downChart = $last7Days->pluck('down_count')->toArray();
        $unstableChart = $last7Days->map(fn($d) => 0)->toArray(); // daily_stats doesn't track unstable separately per day

        return [
            Stat::make('Total Customers', Customer::count()),
            Stat::make('Up', Customer::where('status', 'up')->count())
                ->description('Customers currently UP')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($upChart)
                ->color('success'),
            Stat::make('Unstable', Customer::where('status', 'unstable')->count())
                ->description('Customers currently UNSTABLE')
                ->chart($unstableChart)
                ->color('warning'),
            Stat::make('Down', Customer::criticallyDown()->count())
                ->description('Customers CRITICALLY DOWN')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart($downChart)
                ->color('danger'),
        ];
    }
}
