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
    public function handle()
    {
        $customers = Customer::all();
        $total = $customers->count();
        $this->output->progressStart($total);
        
        // Chunk size for concurrency
        $chunkSize = 100;
        
        $customers->chunk($chunkSize)->each(function ($chunk) {
            $running = [];
            foreach ($chunk as $customer) {
                 $running[$customer->id] = \Illuminate\Support\Facades\Process::start("ping -c 1 -W 1 {$customer->ip_address}");
            }
            
            $results = [];
            while (count($running) > 0) {
                foreach ($running as $id => $proc) {
                    if (! $proc->running()) {
                        $results[$id] = $proc->wait();
                        unset($running[$id]);
                    }
                }
                usleep(10000); // 10ms for tighter loop
            }

            // \Illuminate\Support\Facades\Log::info("HealthCheck: Results collected (Manual Parallel).");
            
            foreach ($results as $customerId => $result) {
                // $customer = $chunk->find($customerId); // Works with ID keys
                $customer = \App\Models\Customer::find($customerId);
                $output = $result->output();
                $errorOutput = $result->errorOutput();
                $exitCode = $result->exitCode();
                
                // if (!empty($errorOutput)) Log::error(...)
                
                // if ($exitCode !== 0) { ... }
                
                // if (empty($output) ... ) { ... }
                
                // ... (Parsing Logic)
                $status = 'down';
                $latency = null;
                $packetLoss = 100;

                if (preg_match('/(\d+)% packet loss/', $output, $matches)) {
                    $packetLoss = (float) $matches[1];
                }
                if (preg_match('/rtt min\/avg\/max\/mdev = [\d\.]+\/([\d\.]+)\//', $output, $matches)) {
                    $latency = (float) $matches[1];
                }

                if ($packetLoss < 20) {
                    $status = 'up';
                } elseif ($packetLoss < 100) {
                    $status = 'unstable';
                }

                $customer->healthChecks()->create([
                    'status' => $status,
                    'latency_ms' => $latency,
                    'packet_loss' => $packetLoss,
                    'checked_at' => now(),
                ]);

                $this->updateCustomerStatus($customer, $status);
                $this->output->progressAdvance();
            }
        });

        $this->output->progressFinish();
        $this->info('Health check completed.');
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
