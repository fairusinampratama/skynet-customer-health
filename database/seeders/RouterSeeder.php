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
                'name' => 'Demo Core Router',
                'ip_address' => '192.0.2.10',
                'port' => 8728,
                'username' => 'demo-router-user',
                'password' => 'replace-with-private-secret',
                'is_active' => true,
            ],
            [
                'name' => 'Demo PPPoE Router',
                'ip_address' => '192.0.2.11',
                'port' => 8728,
                'username' => 'demo-router-user',
                'password' => 'replace-with-private-secret',
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
