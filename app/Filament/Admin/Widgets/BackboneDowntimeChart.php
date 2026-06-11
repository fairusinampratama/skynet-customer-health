<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BackboneDowntimeChart extends ChartWidget
{
    protected ?string $heading = 'Downtime Backbone';

    protected static ?int $sort = 91;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    protected ?string $maxHeight = '360px';

    protected function getData(): array
    {
        $labels = ['IIX', 'JKTIX', 'TLN', 'Data Utama', 'THC', 'RBN'];

        $data = Cache::remember('widget_backbone_downtime_current', 60, function () {
            return DB::table('servers')
                ->where('servers.status', 'down')
                ->where(function ($query) {
                    $query->whereRaw('UPPER(servers.name) LIKE ?', ['%IIX%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%JKTIX%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%TLN%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%DATAUTAMA%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%DATA UTAMA%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%THC%'])
                        ->orWhereRaw('UPPER(servers.name) LIKE ?', ['%RBN%']);
                })
                ->selectRaw("
                    CASE
                        WHEN UPPER(servers.name) LIKE '%JKTIX%' THEN 'JKTIX'
                        WHEN UPPER(servers.name) LIKE '%IIX%' THEN 'IIX'
                        WHEN UPPER(servers.name) LIKE '%TLN%' THEN 'TLN'
                        WHEN UPPER(servers.name) LIKE '%DATAUTAMA%' OR UPPER(servers.name) LIKE '%DATA UTAMA%' THEN 'Data Utama'
                        WHEN UPPER(servers.name) LIKE '%THC%' THEN 'THC'
                        WHEN UPPER(servers.name) LIKE '%RBN%' THEN 'RBN'
                    END as backbone
                ")
                ->selectRaw('SUM(GREATEST(TIMESTAMPDIFF(MINUTE, COALESCE(servers.last_seen, servers.updated_at, servers.created_at), NOW()), 1)) as downtime_minutes')
                ->groupBy('backbone')
                ->pluck('downtime_minutes', 'backbone');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Minutes Downtime',
                    'data' => collect($labels)->map(fn ($label) => (int) ($data[$label] ?? 0))->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.82)',
                    'borderColor' => 'rgb(37, 99, 235)',
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'top'],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'title' => ['display' => true, 'text' => 'Backbone'],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'title' => ['display' => true, 'text' => 'Minutes Downtime'],
                ],
            ],
        ];
    }
}
