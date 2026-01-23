<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Services\PingService;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check health status of all customers';

    /**
     * Execute the console command.
     */
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shouldLogHistory = now()->minute === 0;

        if ($shouldLogHistory) {
             $this->info('Hourly check: Updating real-time status AND logging to history.');
        } else {
             $this->info('Real-time check: Updating status only (History skipped).');
        }

        $this->checkCustomers($shouldLogHistory);
        $this->checkServers($shouldLogHistory);
        
        $this->info('Health check completed for all systems.');
    }

    protected function checkServers($shouldLogHistory = false)
    {
        $this->info('Starting Server Health Check...');
        $servers = \App\Models\Server::all();
        $total = $servers->count();
        $this->output->progressStart($total);
        $chunkSize = 50; // Smaller chunk for critical infra

        if (env('SIMULATE_HEALTH_CHECKS', false)) {
            $servers->each(function ($server) use ($shouldLogHistory) {
                // Simulation: 95% UP
                $rand = rand(1, 100);
                if ($rand <= 95) {
                    $status = 'up';
                    $latency = rand(1, 10);
                    $packetLoss = 0;
                } else {
                    $status = 'down';
                    $latency = null;
                    $packetLoss = 100;
                }

                if ($shouldLogHistory) {
                    $server->healthChecks()->create([
                        'status' => $status,
                        'latency_ms' => $latency,
                        'packet_loss' => $packetLoss,
                        'checked_at' => now(),
                    ]);
                }

                $server->update([
                    'status' => $status,
                    'latency_ms' => $latency,
                    'packet_loss' => $packetLoss,
                    'last_seen' => $status === 'up' ? now() : $server->last_seen,
                ]);
                
                $this->output->progressAdvance();
            });
        } else {
            // Real Ping Logic (Reused Pattern)
            $servers->chunk($chunkSize)->each(function ($chunk) use ($shouldLogHistory) {
                $running = [];
                $isWindows = PHP_OS_FAMILY === 'Windows';
                
                foreach ($chunk as $server) {
                     $cmd = $isWindows 
                        ? "ping -n 1 -w 1000 {$server->ip_address}" 
                        : "ping -c 1 -W 1 {$server->ip_address}";
                     $running[$server->id] = \Illuminate\Support\Facades\Process::start($cmd);
                }
                
                $results = [];
                while (count($running) > 0) {
                    foreach ($running as $id => $proc) {
                        if (! $proc->running()) {
                            $results[$id] = $proc->wait();
                            unset($running[$id]);
                        }
                    }
                    usleep(10000); 
                }
    
                foreach ($results as $serverId => $result) {
                    $server = \App\Models\Server::find($serverId);
                    $output = $result->output();
                    
                    $status = 'down';
                    $latency = null;
                    $packetLoss = 100;
                    
                    if (preg_match('/(\d+)% (?:packet )?loss/', $output, $matches)) {
                        $packetLoss = (float) $matches[1];
                    }
                    
                    if (preg_match('/rtt min\/avg\/max\/mdev = [\d\.]+\/([\d\.]+)\//', $output, $matches)) {
                        $latency = (float) $matches[1];
                    } elseif (preg_match('/Average\s*=\s*(\d+)ms/i', $output, $matches)) {
                        $latency = (float) $matches[1];
                    }
    
                    if ($packetLoss < 20) {
                        $status = 'up';
                    } elseif ($packetLoss < 100) {
                        $status = 'unstable';
                    }
    
                    // Log history if it's the top of the hour OR if the status is not 'up'
                    if ($shouldLogHistory || $status !== 'up') {
                        $server->healthChecks()->create([
                            'status' => $status,
                            'latency_ms' => $latency,
                            'packet_loss' => $packetLoss,
                            'checked_at' => now(),
                        ]);
                    }
    
                    $server->update([
                        'status' => $status,
                        'latency_ms' => $latency,
                        'packet_loss' => $packetLoss,
                        'last_seen' => $status === 'up' ? now() : $server->last_seen,
                    ]);
                    
                    $this->checkServerAlert($server);
                    
                    $this->output->progressAdvance();
                }
            });
        }
        $this->output->progressFinish();
    }
    
    protected function checkServerAlert(\App\Models\Server $server)
    {
        // 1. Check if server is DOWN
        if ($server->status !== 'down') {
            return;
        }
        
        // 2. Check duration > 5 mins
        // Use last_seen (time it was last UP) to calculate how long it's been down.
        // If updated_at is used, it resets every health check if any metric changes (packet loss, etc), breaking the timer.
        
        if (! $server->last_seen) {
            // New server or never seen up. Skip or use created_at. 
            // For now, let's strictly require it to have been seen at least once to know it "went" down.
            return;
        }

        $downSince = $server->last_seen;
        
        if ($downSince->diffInMinutes(now()) >= 5) {
             // 3. Check if already alerted recently
             if (! $server->last_alerted_at || $server->last_alerted_at < $downSince) {
                 $this->sendServerAlert($server, $downSince);
                 
                 // Update last_alerted_at
                 $server->last_alerted_at = now();
                 $server->save();
             }
        }
    }

    protected function sendServerAlert(\App\Models\Server $server, $downSince)
    {
        $whatsAppService = app(\App\Services\WhatsApp\WhatsAppService::class);
        $groupId = config('services.whatsapp.audit_group_id', env('WHATSAPP_AUDIT_GROUP_ID'));

        if (! $groupId) {
            $this->warn("WhatsApp Audit Group ID not configured. Cannot send server alert for {$server->name}.");
            return;
        }

        $url = route('filament.admin.resources.servers.edit', $server);
        
        $message = "🚨 *CRITICAL: SERVER DOWN*\n\n" .
            "🖥️ *Server:* {$server->name}\n" .
            "🌍 *IP:* {$server->ip_address}\n" .
            "⏱️ *Duration:* {$downSince->diffForHumans()}\n\n" .
            "🔗 [View Dashboard]({$url})";
            
        $whatsAppService->sendMessageToGroup($groupId, $message);
        $this->info("Sent WhatsApp alert for server {$server->name}");
    }

    protected function checkCustomers($shouldLogHistory = false)
    {
        $this->info('Starting Customer Health Check...');
        $customers = Customer::all();
        $total = $customers->count();
        $this->output->progressStart($total);
        
        // Chunk size for concurrency
        $chunkSize = 100;
        
        if (env('SIMULATE_HEALTH_CHECKS', false)) {
            $this->info('Running in SIMULATION MODE.');
            $customers->chunk($chunkSize)->each(function ($chunk) use ($shouldLogHistory) {
                foreach ($chunk as $customer) {
                    // Random simulation (weighted)
                    $rand = rand(1, 100);
                    
                    if ($rand <= 90) { 
                        // 90% UP
                        $status = 'up';
                        $latency = rand(2, 50);
                        $packetLoss = 0;
                    } elseif ($rand <= 95) {
                        // 5% UNSTABLE
                        $status = 'unstable';
                        $latency = rand(100, 500);
                        $packetLoss = rand(10, 30);
                    } else {
                        // 5% DOWN
                        $status = 'down';
                        $latency = null;
                        $packetLoss = 100;
                    }
 
                    // Log history if it's the top of the hour OR if the status is not 'up' (to track downtime minutes)
                    if ($shouldLogHistory || $status !== 'up') {
                        $customer->healthChecks()->create([
                            'status' => $status,
                            'latency_ms' => $latency,
                            'packet_loss' => $packetLoss,
                            'checked_at' => now(),
                        ]);
                    }
                    
                    // Always cache latest metrics on the customer model
                    $customer->update([
                        'latency_ms' => $latency,
                        'packet_loss' => $packetLoss,
                    ]);
 
                    $this->updateCustomerStatus($customer, $status);
                    $this->output->progressAdvance();
                }
            });
        } else {
            // Real Ping Logic
            $customers->chunk($chunkSize)->each(function ($chunk) use ($shouldLogHistory) {
                $running = [];
                $isWindows = PHP_OS_FAMILY === 'Windows';
                
                foreach ($chunk as $customer) {
                     // Windows uses -n for count and -w for timeout (ms)
                     // Linux uses -c for count and -W for timeout (s)
                     $cmd = $isWindows 
                        ? "ping -n 1 -w 1000 {$customer->ip_address}" 
                        : "ping -c 1 -W 1 {$customer->ip_address}";
                        
                     $running[$customer->id] = \Illuminate\Support\Facades\Process::start($cmd);
                }
                
                $results = [];
                while (count($running) > 0) {
                    foreach ($running as $id => $proc) {
                        if (! $proc->running()) {
                            $results[$id] = $proc->wait();
                            unset($running[$id]);
                        }
                    }
                    usleep(10000); // 10ms
                }
    
                foreach ($results as $customerId => $result) {
                    $customer = \App\Models\Customer::find($customerId);
                    $output = $result->output();
                    
                    $status = 'down';
                    $latency = null;
                    $packetLoss = 100;
    
                    // Parsing Packet Loss (Unified)
                    // Linux: "100% packet loss"
                    // Windows: "Lost = 0 (0% loss)"
                    if (preg_match('/(\d+)% (?:packet )?loss/', $output, $matches)) {
                        $packetLoss = (float) $matches[1];
                    }
                    
                    // Parsing Latency (OS Specific)
                    // Linux: rtt min/avg/max/mdev = 1.000/2.000/3.000/0.000 ms
                    if (preg_match('/rtt min\/avg\/max\/mdev = [\d\.]+\/([\d\.]+)\//', $output, $matches)) {
                        $latency = (float) $matches[1];
                    } 
                    // Windows: Minimum = 0ms, Maximum = 0ms, Average = 2ms
                    elseif (preg_match('/Average\s*=\s*(\d+)ms/i', $output, $matches)) {
                        $latency = (float) $matches[1];
                    }
    
                    if ($packetLoss < 20) {
                        $status = 'up';
                    } elseif ($packetLoss < 100) {
                        $status = 'unstable';
                    }
    
                    // Log history if it's the top of the hour OR if the status is not 'up'
                    if ($shouldLogHistory || $status !== 'up') {
                        $customer->healthChecks()->create([
                            'status' => $status,
                            'latency_ms' => $latency,
                            'packet_loss' => $packetLoss,
                            'checked_at' => now(),
                        ]);
                    }
                    
                    // Always cache latest metrics on the customer model
                    $customer->update([
                        'latency_ms' => $latency,
                        'packet_loss' => $packetLoss,
                    ]);
    
                    $this->updateCustomerStatus($customer, $status);
                    $this->output->progressAdvance();
                }
            });
        }
 
        $this->output->progressFinish();
    }

    protected function updateCustomerStatus($customer, $newStatus)
    {
        if ($customer->status !== $newStatus) {
            $customer->update(['status' => $newStatus]);
            
            if ($newStatus === 'down') {
                $downSince = $customer->updated_at;
                // Check if down for > 5 minutes (based on updated_at which was just touched if status changed? No, updated_at updates on change.)
                // Actually, if status JUST changed to down, it's 0 minutes down.
                // Alert logic usually runs on subsequent checks.
                // Detailed logic:
                // If just went down -> do nothing (wait 5 mins)
                // If was down and still down -> check time.
                // But this method only runs on CHANGE.
                
                // So purely on change, we don't alert yet.
                // The alert check needs to be separate or we need to handle "still down" cases.
            }
        }
        
        // Check for alerts regardless of status change (consistency check)
        if ($customer->refresh()->status === 'down') {
             $downSince = $customer->updated_at; // Time it changed to down
             if ($downSince->diffInMinutes(now()) >= 5) {
                // Skip if isolated
                if ($customer->is_isolated) {
                    return;
                }

                if (! $customer->last_alerted_at || $customer->last_alerted_at < $downSince) {
                    $this->sendTelegramAlert($customer, $downSince);
                    $customer->update(['last_alerted_at' => now()]);
                }
             }
        }
    }

    protected function sendTelegramAlert(Customer $customer, $downSince)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (! $token || ! $chatId) {
            $this->warn("Telegram credentials not set for {$customer->name}");
            return;
        }

        $message = "🔴 *Customer DOWN*\n" .
            "Name: {$customer->name}\n" .
            "Area: {$customer->area->name}\n" .
            "Since: {$downSince->diffForHumans()}\n" .
            "IP: {$customer->ip_address}";

        \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ]);
    }
}
