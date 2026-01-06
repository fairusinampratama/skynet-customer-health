<?php

use App\Models\Area;
use App\Models\Customer;

use Illuminate\Support\Facades\Schema;

Schema::disableForeignKeyConstraints();
Customer::truncate();
Area::truncate();
DB::table('health_checks')->truncate();
Schema::enableForeignKeyConstraints();

$area = Area::create(['name' => 'Test Area']);

Customer::create(['area_id' => $area->id, 'name' => 'Localhost', 'ip_address' => '127.0.0.1']);
Customer::create(['area_id' => $area->id, 'name' => 'Google DNS', 'ip_address' => '8.8.8.8']);
Customer::create(['area_id' => $area->id, 'name' => 'Dead IP', 'ip_address' => '192.0.2.200']); // Testnet reserved, should fail

echo "Seeded 3 customers.\n";
