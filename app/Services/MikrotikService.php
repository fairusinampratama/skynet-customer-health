<?php

namespace App\Services;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;
use Exception;

class MikrotikService
{
    public function connect(Router $router): Client
    {
        $config = (new Config())
            ->set('host', $router->ip_address)
            ->set('port', (int) $router->port)
            ->set('user', $router->username)
            ->set('pass', $router->password)
            ->set('timeout', 5) // 5s timeout
            ->set('attempts', 1);

        return new Client($config);
    }

    public function fetchHealth(Router $router): array
    {
        $client = $this->connect($router);

        // Get Resource
        $query = new Query('/system/resource/print');
        $response = $client->query($query)->read();
        
        if (empty($response)) {
            throw new Exception("Empty response from Mikrotik resource print.");
        }
        $data = $response[0];

        // Get Health
        $healthQuery = new Query('/system/health/print');
        $healthResponse = $client->query($healthQuery)->read();
        
        $temperature = 0;
        if (!empty($healthResponse)) {
             $healthData = $healthResponse[0];
             // Logic to find temp (same as Job)
             if (isset($healthData['temperature'])) {
                 $temperature = (int) $healthData['temperature'];
             } elseif (isset($healthData['cpu-temperature'])) {
                 $temperature = (int) $healthData['cpu-temperature'];
             } else {
                 foreach ($healthResponse as $item) {
                     if (isset($item['name']) && $item['name'] === 'temperature' && isset($item['value'])) {
                         $temperature = (int) $item['value'];
                         break;
                     }
                 }
             }
        }

        // Metrics
        $cpu = isset($data['cpu-load']) ? (int) $data['cpu-load'] : 0;
        $freeMem = isset($data['free-memory']) ? (int) $data['free-memory'] : 0;
        $totalMem = isset($data['total-memory']) ? (int) $data['total-memory'] : 0;
        $freeHdd = isset($data['free-hdd-space']) ? (float) $data['free-hdd-space'] : 0;
        $totalHdd = isset($data['total-hdd-space']) ? (float) $data['total-hdd-space'] : 0;
        
        $diskUsage = 0;
        if ($totalHdd > 0) {
             $diskUsage = round((($totalHdd - $freeHdd) / $totalHdd) * 100, 2);
        }

        return [
            'cpu_load' => $cpu,
            'temperature' => $temperature,
            'free_memory' => $freeMem,
            'total_memory' => $totalMem,
            'disk_usage' => $diskUsage,
            'status' => 'up',
            'last_seen' => now(),
        ];
    }
}
