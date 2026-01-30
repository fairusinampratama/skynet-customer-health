<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Router;

class RouterSeeder extends Seeder
{
    public function run()
    {
        $routers = [
            [
                'name' => 'Skynet-Metro',
                'ip_address' => '172.22.254.1',
                'port' => 8728,
                'username' => 'API-AAM',
                'password' => 'aamprogrammer2026',
                'is_active' => true,
            ],
            [
                'name' => 'Skynet-PPPoE Randuagung',
                'ip_address' => '10.181.40.2',
                'port' => 8728,
                'username' => 'API-AAM',
                'password' => 'aamprogrammer2026',
                'is_active' => true,
            ],
        ];

        foreach ($routers as $data) {
            Router::updateOrCreate(
                ['ip_address' => $data['ip_address']],
                $data
            );
            $this->command->info("Router {$data['name']} added.");
        }
    }
}
