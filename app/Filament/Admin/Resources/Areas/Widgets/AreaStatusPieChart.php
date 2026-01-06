<?php

namespace App\Filament\Admin\Resources\Areas\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;

class AreaStatusPieChart extends ChartWidget
{
    // The record will be injected by Filament on the View/Edit page
    public ?Model $record = null;

    protected ?string $heading = 'Area Status Distribution';
    
    protected ?string $pollingInterval = '5s';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        if (! $this->record) {
             return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $up = $this->record->customers()->where('status', 'up')->count();
        $unstable = $this->record->customers()->where('status', 'unstable')->count();
        $down = $this->record->customers()->where('status', 'down')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Customers',
                    'data' => [$up, $unstable, $down],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)', // Green
                        'rgb(234, 179, 8)', // Yellow
                        'rgb(239, 68, 68)', // Red
                    ],
                ],
            ],
            'labels' => [
                "Up ({$up})", 
                "Unstable ({$unstable})", 
                "Down ({$down})"
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'font' => [
                            'size' => 14,
                        ],
                    ],
                ],
            ],
        ];
    }
}
