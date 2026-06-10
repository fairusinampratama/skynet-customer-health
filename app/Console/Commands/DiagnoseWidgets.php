<?php

namespace App\Console\Commands;

use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseWidgets extends Command
{
    protected $name = 'diagnose:widgets';
    protected $description = 'Diagnose Filament widget registration and data availability';

    public function handle(): int
    {
        $this->info('=== Filament Widget Diagnostic ===');
        $this->newLine();

        // 1. Check widget registration
        $this->info('1. Widget Registration:');
        try {
            $panels = Filament::getPanels();
            foreach ($panels as $name => $panel) {
                $widgets = $panel->getWidgets();
                $this->line("   Panel: {$name} — " . count($widgets) . ' widgets');
                foreach ($widgets as $w) {
                    $check = class_exists($w) ? '✅' : '❌ MISSING';
                    $this->line("     {$check} {$w}");
                }
            }
        } catch (\Throwable $e) {
            $this->error("   Error: {$e->getMessage()}");
        }

        // 2. Check Filament cache
        $this->newLine();
        $this->info('2. Filament Component Cache:');
        $cacheFile = base_path('bootstrap/cache/filament/panels/admin.php');
        if (file_exists($cacheFile)) {
            $this->line('   Cache file exists: ✅');
            $cache = include $cacheFile;
            $cachedWidgets = $cache['widgets'] ?? [];
            $this->line('   Cached widget classes: ' . count($cachedWidgets));
            foreach ($cachedWidgets as $key => $class) {
                $prefix = is_int($key) ? "[explicit:{$key}]" : "[discovered]";
                $this->line("     {$prefix} {$class}");
            }
            // Check for chart widgets
            $hasGangguan = false;
            $hasSla = false;
            foreach ($cachedWidgets as $class) {
                if (str_contains($class, 'GangguanPerAreaBarChart')) $hasGangguan = true;
                if (str_contains($class, 'SlaDowntimeChart')) $hasSla = true;
            }
            $this->line('   GangguanPerAreaBarChart in cache: ' . ($hasGangguan ? '✅' : '❌'));
            $this->line('   SlaDowntimeChart in cache: ' . ($hasSla ? '✅' : '❌'));
        } else {
            $this->warn('   Cache file NOT found — widgets discovered at runtime');
        }

        // 3. Check database data
        $this->newLine();
        $this->info('3. Database Data Check:');
        try {
            if (Schema::hasTable('health_checks')) {
                $total = DB::table('health_checks')->count();
                $last7days = DB::table('health_checks')
                    ->where('checked_at', '>=', now()->subDays(7))
                    ->count();
                $downCount = DB::table('health_checks')
                    ->where('checked_at', '>=', now()->subDays(7))
                    ->where('status', 'down')
                    ->count();
                $this->line("   Total health_checks: {$total}");
                $this->line("   Last 7 days: {$last7days}");
                $this->line("   Down events (7d): {$downCount}");
                
                $latestCheck = DB::table('health_checks')->orderByDesc('checked_at')->first();
                if ($latestCheck) {
                    $this->line("   Latest check: {$latestCheck->checked_at}");
                } else {
                    $this->warn('   No health_checks records at all!');
                }
            } else {
                $this->error('   health_checks table does not exist!');
            }
        } catch (\Throwable $e) {
            $this->error("   DB error: {$e->getMessage()}");
        }

        // 4. Test chart widget getData
        $this->newLine();
        $this->info('4. Chart Widget Data Test:');
        try {
            $chart = new \App\Filament\Admin\Widgets\GangguanPerAreaBarChart();
            $ref = new \ReflectionMethod($chart, 'getData');
            $ref->setAccessible(true);
            $data = $ref->invoke($chart);
            $this->line('   GangguanPerAreaBarChart labels: ' . json_encode($data['labels'] ?? []));
            $this->line('   GangguanPerAreaBarChart values: ' . json_encode($data['datasets'][0]['data'] ?? []));
            
            $chart2 = new \App\Filament\Admin\Widgets\SlaDowntimeChart();
            $ref2 = new \ReflectionMethod($chart2, 'getData');
            $ref2->setAccessible(true);
            $data2 = $ref2->invoke($chart2);
            $this->line('   SlaDowntimeChart labels: ' . json_encode($data2['labels'] ?? []));
            $this->line('   SlaDowntimeChart values: ' . json_encode($data2['datasets'][0]['data'] ?? []));
        } catch (\Throwable $e) {
            $this->error("   Chart error: {$e->getMessage()}");
        }

        // 5. App version
        $this->newLine();
        $this->info('5. Code Version:');
        $gitHead = base_path('.git/HEAD');
        if (file_exists($gitHead)) {
            $ref = file_get_contents($gitHead);
            $refFile = base_path('.git/' . trim(str_replace('ref: ', '', $ref)));
            if (file_exists($refFile)) {
                $this->line('   Git commit: ' . substr(trim(file_get_contents($refFile)), 0, 7));
            }
        }
        $this->line('   Latest expected: ef0ad19 (v4 fix)');

        $this->newLine();
        $this->info('=== Diagnostic Complete ===');

        return 0;
    }
}
