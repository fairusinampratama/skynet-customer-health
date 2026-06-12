<?php

namespace App\Filament\Admin\Widgets;

use Carbon\CarbonImmutable;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BackboneDowntimeChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Downtime Backbone';

    protected static ?int $sort = 91;

    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '390px';

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('month')
                    ->label('Month')
                    ->type('month')
                    ->default(now()->format('Y-m')),
            ]);
    }

    public function updatedFilters(): void
    {
        $this->cachedData = null;
        $this->updateChartData();
    }

    protected function getData(): array
    {
        $labels = ['IIX', 'JKTIX', 'TLN', 'Data Utama', 'THC', 'RBN'];
        [$startDate, $endDate] = $this->getMonthRange();

        $cacheKey = sprintf(
            'widget_backbone_downtime_%s_%s',
            $startDate->toDateString(),
            $endDate->toDateString(),
        );

        $data = Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            return DB::table('server_health_daily_stats')
                ->whereBetween('date', [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ])
                ->whereNotNull('backbone_name')
                ->select('backbone_name')
                ->selectRaw('SUM(down_count) as downtime_minutes')
                ->groupBy('backbone_name')
                ->pluck('downtime_minutes', 'backbone_name');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Minutes Downtime',
                    'data' => collect($labels)->map(fn ($label) => (int) ($data[$label] ?? 0))->toArray(),
                    'backgroundColor' => [
                        'rgba(37, 99, 235, 0.78)',
                        'rgba(14, 165, 233, 0.78)',
                        'rgba(20, 184, 166, 0.78)',
                        'rgba(99, 102, 241, 0.78)',
                        'rgba(168, 85, 247, 0.78)',
                        'rgba(244, 63, 94, 0.78)',
                    ],
                    'borderColor' => [
                        'rgb(29, 78, 216)',
                        'rgb(2, 132, 199)',
                        'rgb(13, 148, 136)',
                        'rgb(79, 70, 229)',
                        'rgb(147, 51, 234)',
                        'rgb(225, 29, 72)',
                    ],
                    'borderWidth' => 1,
                    'borderRadius' => 3,
                    'barPercentage' => 0.62,
                    'categoryPercentage' => 0.72,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            layout: {
                padding: { top: 20, right: 12, bottom: 4, left: 4 }
            },
            maintainAspectRatio: false,
            animation: {
                duration: 350,
                onComplete: ({ chart }) => {
                    const { ctx } = chart;
                    ctx.save();
                    ctx.font = '600 10px Inter, system-ui, sans-serif';
                    ctx.fillStyle = '#334155';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';

                    chart.data.datasets.forEach((dataset, datasetIndex) => {
                        const meta = chart.getDatasetMeta(datasetIndex);

                        meta.data.forEach((bar, index) => {
                            const value = dataset.data[index] ?? 0;
                            if (value <= 0) {
                                return;
                            }

                            ctx.fillText(value.toLocaleString() + 'm', bar.x, Math.max(bar.y - 4, 12));
                        });
                    });

                    ctx.restore();
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    displayColors: false,
                    callbacks: {
                        label: (context) => `${context.parsed.y.toLocaleString()} minutes downtime`
                    }
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11, weight: '600' }
                    },
                    title: {
                        display: true,
                        text: 'Backbone',
                        color: '#475569',
                        font: { size: 11, weight: '600' }
                    },
                },
                y: {
                    beginAtZero: true,
                    grace: '12%',
                    grid: {
                        color: 'rgba(148, 163, 184, 0.22)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#64748b',
                        precision: 0,
                        callback: (value) => value.toLocaleString()
                    },
                    title: {
                        display: true,
                        text: 'Minutes Downtime',
                        color: '#475569',
                        font: { size: 11, weight: '600' }
                    },
                },
            },
            interaction: {
                mode: 'nearest',
                intersect: false
            }
        }
        JS);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function getMonthRange(): array
    {
        $month = $this->filters['month'] ?? now()->format('Y-m');

        if (! is_string($month) || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        try {
            $startDate = CarbonImmutable::createFromFormat('Y-m-d H:i:s', "{$month}-01 00:00:00")->startOfMonth();
        } catch (\Throwable) {
            $startDate = CarbonImmutable::now()->startOfMonth();
        }

        return [$startDate, $startDate->endOfMonth()];
    }
}
