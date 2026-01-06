<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

class PingService
{
    /**
     * Ping an IP address and return health status.
     *
     * @param string $ip
     * @return array{status: string, latency_ms: ?int, packet_loss: float}
     */
    public function ping(string $ip): array
    {
        // 3 pings, 1 second wait each
        $command = sprintf("ping -c 3 -W 1 %s", escapeshellarg($ip));

        exec($command, $output, $resultCode);
        $outputStr = implode("\n", $output);

        // 1. Calculate Packet Loss
        // "3 packets transmitted, 3 received, 0% packet loss, time 2003ms"
        $loss = 100; // Default to 100% loss (down)
        if (preg_match('/(\d+)% packet loss/', $outputStr, $matches)) {
            $loss = (float) $matches[1];
        }

        // 2. Calculate Latency (avg)
        // "rtt min/avg/max/mdev = 14.321/24.500/32.112/5.123 ms"
        $latency = null;
        if (preg_match('/rtt min\/avg\/max\/mdev = [\d.]+\/([\d.]+)\/[\d.]+\/[\d.]+ ms/', $outputStr, $matches)) {
            $latency = (int) $matches[1];
        }

        // 3. Determine Status based on LOSS
        // 0-20% loss = UP
        // 21-99% loss = UNSTABLE
        // 100% loss = DOWN
        $status = 'down';
        if ($loss == 0) {
            $status = 'up';
        } elseif ($loss < 100) {
            $status = 'unstable';
        }

        return [
            'status' => $status,
            'latency_ms' => $latency,
            'packet_loss' => $loss,
        ];
    }
}
