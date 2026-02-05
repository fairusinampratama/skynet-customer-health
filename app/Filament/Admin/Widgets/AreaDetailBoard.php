<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Area;
use App\Models\Customer;

class AreaDetailBoard extends Widget
{
    protected string $view = 'filament.admin.widgets.area-detail-board';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '15s';

    public ?int $areaId = null;
    public ?Area $area = null;

    public function mount($areaId = null)
    {
        $this->areaId = $areaId;
        if ($this->areaId) {
            $this->area = Area::find($this->areaId);
        }
    }

    public function getCustomersProperty()
    {
        if (!$this->area) return [];

        return $this->area->customers()
            ->orderByRaw("CASE WHEN status = 'down' THEN 1 WHEN status = 'unstable' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();
    }

    public function getStatsProperty()
    {
        if (!$this->area) return ['up' => 0, 'down' => 0, 'total' => 0, 'score' => 0];

        $total = $this->area->customers()->where('is_isolated', false)->count();
        $up = $this->area->customers()->where('is_isolated', false)->where('status', 'up')->count();
        $down = $this->area->customers()->where('is_isolated', false)->where('status', 'down')->count();
        
        $score = $total > 0 ? round(($up / $total) * 100, 1) : 0;

        return [
            'up' => $up,
            'down' => $down,
            'total' => $total,
            'score' => $score
        ];
    }
}
