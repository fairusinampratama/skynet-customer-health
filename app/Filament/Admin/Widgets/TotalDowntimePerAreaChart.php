<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Area;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TotalDowntimePerAreaChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Total Downtime per Area';

    protected static ?int $sort = 90;

    protected static bool $isLazy = true;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '430px';

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('month')
                    ->label('Month')
                    ->type('month')
                    ->default(now()->format('Y-m')),
                Select::make('areaId')
                    ->label('Area')
                    ->placeholder('All areas')
                    ->options(fn (): array => Area::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->native(false),
            ])
            ->columns(1);
    }

    public function updatedFilters(): void
    {
        $this->cachedData = null;
        $this->updateChartData();
    }

    protected function getData(): array
    {
        [$startDate, $endDate] = $this->getMonthRange();
        $areaId = filled($this->filters['areaId'] ?? null)
            ? (int) $this->filters['areaId']
            : null;

        $cacheKey = sprintf(
            'widget_total_downtime_per_area_%s_%s_%s',
            $startDate->toDateString(),
            $endDate->toDateString(),
            $areaId ?: 'all',
        );

        $data = Cache::remember($cacheKey, 300, function () use ($startDate, $endDate, $areaId) {
            return DB::table('areas')
                ->when($areaId, fn ($query) => $query->where('areas.id', $areaId))
                ->leftJoin('health_check_daily_stats', function ($join) use ($startDate, $endDate) {
                    $join->on('health_check_daily_stats.area_id', '=', 'areas.id')
                        ->whereBetween('health_check_daily_stats.date', [
                            $startDate->toDateString(),
                            $endDate->toDateString(),
                        ]);
                })
                ->select('areas.name')
                ->selectRaw('COALESCE(SUM(health_check_daily_stats.down_count), 0) as downtime_minutes')
                ->groupBy('areas.id', 'areas.name')
                ->orderByDesc('downtime_minutes')
                ->orderBy('areas.name')
                ->when(! $areaId, fn ($query) => $query->limit(10))
                ->get();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Minutes Downtime',
                    'data' => $data->pluck('downtime_minutes')->map(fn ($value) => (int) $value)->toArray(),
                    'backgroundColor' => 'rgba(14, 165, 233, 0.78)',
                    'borderColor' => 'rgb(2, 132, 199)',
                    'borderWidth' => 1,
                    'borderRadius' => 3,
                    'barPercentage' => 0.72,
                    'categoryPercentage' => 0.82,
                ],
            ],
            'labels' => $data->pluck('name')->toArray(),
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
                        maxRotation: 45,
                        minRotation: 35,
                        autoSkip: false,
                        font: { size: 10 }
                    },
                    title: {
                        display: true,
                        text: 'Area',
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
