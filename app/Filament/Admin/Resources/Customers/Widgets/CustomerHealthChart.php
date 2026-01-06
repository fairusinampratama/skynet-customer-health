<?php

namespace App\Filament\Admin\Resources\Customers\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;

class CustomerHealthChart extends ChartWidget
{
    protected ?string $pollingInterval = '5s';
    
    protected ?string $heading = 'Health History (24h)';

    protected int | string | array $columnSpan = 'full';
    
    public ?Model $record = null;

    protected function getData(): array
    {
        if (! $this->record) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Get health checks from the last 24 hours for this customer
        $data = $this->record->healthChecks()
            ->where('checked_at', '>=', now()->subDay())
            ->oldest('checked_at')
            ->get()
            ->map(fn ($check) => [
                'value' => $check->latency_ms ?? 0,
                'label' => $check->checked_at->format('H:i'),
                'color' => match (true) {
                    $check->status === 'down' => 'rgb(239, 68, 68)', // Red
                    $check->latency_ms > 200 => 'rgb(234, 179, 8)', // Yellow
                    default => 'rgb(34, 197, 94)', // Green
                },
            ]);

        return [
            'datasets' => [
                [
                    'label' => 'Latency (ms) - Lower is Better',
                    'data' => $data->pluck('value')->toArray(),
                    'borderColor' => '#3b82f6',
                    'fill' => 'start',
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
