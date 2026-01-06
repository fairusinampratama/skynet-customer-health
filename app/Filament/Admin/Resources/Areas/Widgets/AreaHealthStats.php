<?php

namespace App\Filament\Admin\Resources\Areas\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class AreaHealthStats extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '5s';
    
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $total = $this->record->customers()->count();
        $up = $this->record->customers()->where('status', 'up')->count();
        $down = $this->record->customers()->where('status', 'down')->count();
        
        // Calculate Percentages
        $upPercent = $total > 0 ? round(($up / $total) * 100) : 0;
        $downPercent = $total > 0 ? round(($down / $total) * 100) : 0;

        return [
            Stat::make('Total Customers', $total),
            Stat::make('Health Score', "{$upPercent}%")
                ->description('Customers Online')
                ->color($upPercent > 90 ? 'success' : 'warning'),
            Stat::make('Critical', $down)
                ->description("{$downPercent}% Customers Down")
                ->color($down > 0 ? 'danger' : 'success'),
        ];
    }
}
