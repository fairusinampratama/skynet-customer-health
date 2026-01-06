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
    public function handle(PingService $pingService)
    {
        $customers = Customer::all();
        $this->output->progressStart($customers->count());

        foreach ($customers as $customer) {
            $result = $pingService->ping($customer->ip_address);
            $pingStatus = $result['status'];

            $customer->healthChecks()->create([
                'status' => $pingStatus,
                'latency_ms' => $result['latency_ms'],
                'packet_loss' => $result['packet_loss'],
                'checked_at' => now(),
            ]);

            // Anti-flap status calculation
            $lastChecks = $customer->healthChecks()->latest('checked_at')->take(3)->pluck('status');
            
            $newStatus = 'unstable';
            if ($lastChecks->count() >= 3) {
                 if ($lastChecks->every(fn($s) => $s === 'up')) {
                     $newStatus = 'up';
                 } elseif ($lastChecks->every(fn($s) => $s === 'down')) {
                     $newStatus = 'down';
                 }
            } elseif ($lastChecks->count() > 0) {
                 // Less than 3 checks, assume unstable unless all match (optional, but safer to start unstable or follow latest)
                 // User said "Last 3 = DOWN", so strict.
                 // Let's just follow the latest status for initial checks or keep it 'unstable'?
                 // "Minimal" approach: if < 3, use latest? Or 'unstable'.
                 // Let's use 'unstable' until we have 3 consistent checks, or maybe if all available are matching.
                 if ($lastChecks->every(fn($s) => $s === 'up')) $newStatus = 'up';
                 elseif ($lastChecks->every(fn($s) => $s === 'down')) $newStatus = 'down';
            }

            if ($customer->status !== $newStatus) {
                $customer->update(['status' => $newStatus]);
            }

            // Alerting Logic
            if ($customer->status === 'down') {
                $downSince = $customer->updated_at;
                if ($downSince->diffInMinutes(now()) >= 5) {
                    // Check if already alerted for this incident
                    if (! $customer->last_alerted_at || $customer->last_alerted_at < $downSince) {
                        $this->sendTelegramAlert($customer, $downSince);
                        $customer->update(['last_alerted_at' => now()]);
                    }
                }
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info('Health check completed.');
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
