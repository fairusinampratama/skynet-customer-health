<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Customers', \App\Models\Customer::count()),
            Stat::make('Up', \App\Models\Customer::where('status', 'up')->count())
                ->description('Customers currently UP')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart(\App\Models\HealthCheck::selectRaw('count(*) as count')
                    ->where('status', 'up')
                    ->groupBy('checked_at')
                    ->latest('checked_at')
                    ->take(10)
                    ->pluck('count')
                    ->toArray()
                )
                ->color('success'),
            Stat::make('Unstable', \App\Models\Customer::where('status', 'unstable')->count())
                ->description('Customers currently UNSTABLE')
                ->chart(\App\Models\HealthCheck::selectRaw('count(*) as count')
                    ->where('status', 'unstable')
                    ->groupBy('checked_at')
                    ->latest('checked_at')
                    ->take(10)
                    ->pluck('count')
                    ->toArray()
                )
                ->color('warning'),
            Stat::make('Down', \App\Models\Customer::where('status', 'down')->where('is_isolated', false)->count())
                ->description('Customers CRITICALLY DOWN')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->chart(\App\Models\HealthCheck::selectRaw('count(*) as count')
                    ->where('status', 'down')
                    ->groupBy('checked_at')
                    ->latest('checked_at')
                    ->take(10)
                    ->pluck('count')
                    ->toArray()
                )
                ->color('danger'),
        ];

    }
}
