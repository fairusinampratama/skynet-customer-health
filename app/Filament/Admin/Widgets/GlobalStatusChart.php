<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Customer;

class GlobalStatusChart extends ChartWidget
{
    protected ?string $heading = 'Network Health Check';
    
    protected ?string $pollingInterval = '5s';

    protected static ?int $sort = 2; // Before Logs, After Overview

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $up = Customer::where('status', 'up')->count();
        $unstable = Customer::where('status', 'unstable')->count();
        $down = Customer::where('status', 'down')->count();

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
