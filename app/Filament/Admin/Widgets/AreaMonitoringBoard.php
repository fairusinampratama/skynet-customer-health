<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class AreaMonitoringBoard extends Widget
{
    protected string $view = 'filament.admin.widgets.area-monitoring-board';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';

    public $displayMode = 'table'; // card, table, chart

    public function mount()
    {
        // Default to table, user can switch
        $this->displayMode = 'table';
    }

    public function setMode($mode)
    {
        $this->displayMode = $mode;
    }

    public function getAreasProperty()
    {
        return \App\Models\Area::query()
            ->withCount(['customers as up_count' => function ($query) {
                $query->where('is_isolated', false)
                      ->where(function ($q) {
                          $q->where('status', 'up')
                            ->orWhere(function ($q2) {
                                $q2->where('status', 'down')
                                   ->where('updated_at', '>', now()->subMinutes(5));
                            });
                      });
            }, 'customers as down_count' => function ($query) {
                $query->where('status', 'down')
                      ->where('is_isolated', false)
                      ->where('updated_at', '<=', now()->subMinutes(5));
            }, 'customers as total_count' => function ($query) {
                 $query->where('is_isolated', false); // Only monitored customers
            }])
            ->get()
            ->map(function ($area) {
                $area->health_score = $area->total_count > 0 
                    ? round(($area->up_count / $area->total_count) * 100, 1)
                    : 0;
                    
                // For chart data (simplified history)
                // In a real scenario, we'd query historical checks properly. 
                // We'll mimic the sparkline data fetch if needed or pass ID for separate loading.
                return $area;
            })
            ->sortByDesc('down_count'); // Default sort: worst first
    }
}
