<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;

class DowntimeMonitoringBoard extends Widget
{
    protected string $view = 'filament.admin.widgets.downtime-monitoring-board';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';
    


    public $displayMode = 'table'; // table, card

    public function mount()
    {
        $this->displayMode = 'table';
    }

    public function setMode($mode)
    {
        $this->displayMode = $mode;
    }

    public function getRecordsProperty()
    {
        return \App\Models\Customer::query()
            ->with(['area', 'latestHealth'])
            ->where('status', 'down')
            ->where('is_isolated', false)
            ->orderBy('updated_at', 'asc') // Oldest downtime first (most critical)
            ->limit(50)
            ->get();
    }
}
