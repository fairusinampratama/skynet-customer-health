<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateServerHealthStats extends Command
{
    protected $signature = 'stats:aggregate-server-health
        {--from= : Start date in YYYY-MM-DD format}
        {--to= : End date in YYYY-MM-DD format}';

    protected $description = 'Aggregate server health checks into daily stats for dashboard charts';

    public function handle(): int
    {
        [$from, $to] = $this->dateRange();

        $this->info("Aggregating server health stats from {$from->toDateString()} to {$to->toDateString()}...");

        $rows = DB::table('server_health_checks')
            ->join('servers', 'servers.id', '=', 'server_health_checks.server_id')
            ->whereBetween('server_health_checks.checked_at', [
                $from->startOfDay()->toDateTimeString(),
                $to->endOfDay()->toDateTimeString(),
            ])
            ->selectRaw('DATE(server_health_checks.checked_at) as date')
            ->selectRaw('server_health_checks.server_id')
            ->selectRaw('servers.name as server_name')
            ->selectRaw("SUM(CASE WHEN server_health_checks.status = 'up' THEN 1 ELSE 0 END) as up_count")
            ->selectRaw("SUM(CASE WHEN server_health_checks.status = 'down' THEN 1 ELSE 0 END) as down_count")
            ->selectRaw("SUM(CASE WHEN server_health_checks.status = 'unstable' THEN 1 ELSE 0 END) as unstable_count")
            ->selectRaw('COUNT(*) as total_count')
            ->groupBy('date', 'server_health_checks.server_id', 'servers.name')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No server health checks found for the selected date range.');

            return self::SUCCESS;
        }

        $now = now();
        $payload = $rows
            ->map(fn ($row): array => [
                'date' => $row->date,
                'server_id' => (int) $row->server_id,
                'backbone_name' => $this->backboneName((string) $row->server_name),
                'up_count' => (int) $row->up_count,
                'down_count' => (int) $row->down_count,
                'unstable_count' => (int) $row->unstable_count,
                'total_count' => (int) $row->total_count,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::table('server_health_daily_stats')->upsert(
            $payload,
            ['date', 'server_id'],
            ['backbone_name', 'up_count', 'down_count', 'unstable_count', 'total_count', 'updated_at'],
        );

        $this->info('Aggregated ' . count($payload) . ' daily server stat rows.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(): array
    {
        $from = $this->parseDate($this->option('from')) ?? CarbonImmutable::today();
        $to = $this->parseDate($this->option('to')) ?? CarbonImmutable::today();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function parseDate(mixed $date): ?CarbonImmutable
    {
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function backboneName(string $serverName): ?string
    {
        $normalized = strtoupper(str_replace([' ', '-', '_'], '', $serverName));

        return match (true) {
            str_contains($normalized, 'JKTIX') => 'JKTIX',
            str_contains($normalized, 'IIX') => 'IIX',
            str_contains($normalized, 'TLN') => 'TLN',
            str_contains($normalized, 'DATAUTAMA') => 'Data Utama',
            str_contains($normalized, 'THC') => 'THC',
            str_contains($normalized, 'RBN') => 'RBN',
            default => null,
        };
    }
}
