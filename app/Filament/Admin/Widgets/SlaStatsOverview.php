<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class SlaStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 5;
    protected ?string $pollingInterval = '60s';
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $since = now()->subDays(7)->toDateString();

        // Use pre-aggregated daily_stats (713 rows) instead of raw health_checks (49M rows)
        $stats = DB::table('health_check_daily_stats')
            ->where('date', '>=', $since)
            ->selectRaw('SUM(down_count) as total_down, SUM(total_count) as total_checks')
            ->first();

        $totalDown = $stats->total_down ?? 0;
        $totalChecks = $stats->total_checks ?? 0;
        $overallSla = $totalChecks > 0 ? round((1 - ($totalDown / $totalChecks)) * 100, 2) : 100;

        // Worst area from daily_stats
        $worstArea = DB::table('health_check_daily_stats')
            ->join('areas', 'health_check_daily_stats.area_id', '=', 'areas.id')
            ->where('health_check_daily_stats.date', '>=', $since)
            ->groupBy('areas.id', 'areas.name')
            ->select('areas.name', DB::raw('SUM(down_count) as down_count'))
            ->orderByDesc('down_count')
            ->first();

        $totalDownCustomers = Customer::where('status', 'down')
            ->where('is_isolated', false)
            ->count();

        // Avg down time per area from daily_stats (approximation)
        $avgDownPerArea = DB::table('health_check_daily_stats')
            ->join('areas', 'health_check_daily_stats.area_id', '=', 'areas.id')
            ->where('health_check_daily_stats.date', '>=', $since)
            ->where('down_count', '>', 0)
            ->selectRaw('AVG(down_count) as avg_down')
            ->first();

        return [
            Stat::make('SLA (7 Hari)', "{$overallSla}%")
                ->description($overallSla >= 99.5 ? 'Target tercapai' : 'Di bawah target 99.5%')
                ->descriptionIcon($overallSla >= 99.5 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($overallSla >= 99.5 ? 'success' : ($overallSla >= 98 ? 'warning' : 'danger')),

            Stat::make('Daerah Terburuk', $worstArea?->name ?? '-')
                ->description($worstArea ? number_format($worstArea->down_count) . ' gangguan' : 'Tidak ada data')
                ->descriptionIcon('heroicon-m-map-pin')
                ->color($worstArea && $worstArea->down_count > 100 ? 'danger' : 'warning'),

            Stat::make('Rata-rata Down/Area', $avgDownPerArea ? number_format(round($avgDownPerArea->avg_down)) : '0')
                ->description('Avg down count per area (7 hari)')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Customer Down Saat Ini', $totalDownCustomers)
                ->description('Dari total ' . Customer::where('is_isolated', false)->count() . ' customer')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($totalDownCustomers > 0 ? 'danger' : 'success'),
        ];
    }
}
