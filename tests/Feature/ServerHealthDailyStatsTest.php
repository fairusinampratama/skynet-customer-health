<?php

use App\Filament\Admin\Widgets\BackboneDowntimeChart;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        $this->markTestSkipped('The pdo_sqlite extension is required for this database-backed test.');
    }

    Artisan::call('migrate:fresh');
});

test('server health stats aggregation upserts daily rows', function () {
    $serverId = DB::table('servers')->insertGetId([
        'name' => 'JKTIX Core Router',
        'ip_address' => '192.0.2.20',
        'status' => 'up',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('server_health_checks')->insert([
        [
            'server_id' => $serverId,
            'status' => 'up',
            'latency_ms' => 3,
            'packet_loss' => 0,
            'checked_at' => '2026-06-11 08:00:00',
        ],
        [
            'server_id' => $serverId,
            'status' => 'down',
            'latency_ms' => null,
            'packet_loss' => 100,
            'checked_at' => '2026-06-11 08:01:00',
        ],
        [
            'server_id' => $serverId,
            'status' => 'unstable',
            'latency_ms' => 120,
            'packet_loss' => 30,
            'checked_at' => '2026-06-11 08:02:00',
        ],
    ]);

    Artisan::call('stats:aggregate-server-health', [
        '--from' => '2026-06-11',
        '--to' => '2026-06-11',
    ]);

    expect(DB::table('server_health_daily_stats')->count())->toBe(1);

    $row = DB::table('server_health_daily_stats')->first();

    expect($row->date)->toBe('2026-06-11')
        ->and($row->server_id)->toBe($serverId)
        ->and($row->backbone_name)->toBe('JKTIX')
        ->and((int) $row->up_count)->toBe(1)
        ->and((int) $row->down_count)->toBe(1)
        ->and((int) $row->unstable_count)->toBe(1)
        ->and((int) $row->total_count)->toBe(3);

    DB::table('server_health_checks')->insert([
        'server_id' => $serverId,
        'status' => 'down',
        'latency_ms' => null,
        'packet_loss' => 100,
        'checked_at' => '2026-06-11 08:03:00',
    ]);

    Artisan::call('stats:aggregate-server-health', [
        '--from' => '2026-06-11',
        '--to' => '2026-06-11',
    ]);

    $updated = DB::table('server_health_daily_stats')->first();

    expect(DB::table('server_health_daily_stats')->count())->toBe(1)
        ->and((int) $updated->down_count)->toBe(2)
        ->and((int) $updated->total_count)->toBe(4);
});

test('backbone chart reads daily stats data', function () {
    $serverId = DB::table('servers')->insertGetId([
        'name' => 'IIX Main',
        'ip_address' => '192.0.2.21',
        'status' => 'up',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('server_health_daily_stats')->insert([
        'date' => '2026-06-11',
        'server_id' => $serverId,
        'backbone_name' => 'IIX',
        'up_count' => 10,
        'down_count' => 7,
        'unstable_count' => 0,
        'total_count' => 17,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('server_health_checks')->insert([
        'server_id' => $serverId,
        'status' => 'down',
        'latency_ms' => null,
        'packet_loss' => 100,
        'checked_at' => '2026-06-11 08:00:00',
    ]);

    $chart = new BackboneDowntimeChart();
    $chart->filters = ['month' => '2026-06'];

    $method = new ReflectionMethod($chart, 'getData');
    $method->setAccessible(true);

    $data = $method->invoke($chart);

    expect($data['labels'])->toBe(['IIX', 'JKTIX', 'TLN', 'Data Utama', 'THC', 'RBN'])
        ->and($data['datasets'][0]['data'][0])->toBe(7);
});
