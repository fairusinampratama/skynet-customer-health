<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\Router;
use App\Services\WhatsApp\WhatsAppService;
use Exception;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;

class CheckRouterHealth implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Router $router
    ) {}

    public function handle(WhatsAppService $whatsAppService, \App\Services\MikrotikService $mikrotikService): void
    {
        if (env('SIMULATE_HEALTH_CHECKS', false)) {
            $this->simulateHealthCheck($whatsAppService);
            return;
        }

        try {
            $metrics = $mikrotikService->fetchHealth($this->router);

            $this->router->update($metrics);
            
            $this->checkThresholds(
                $metrics['cpu_load'], 
                $metrics['free_memory'], 
                $metrics['temperature'], 
                $whatsAppService
            );

        } catch (Exception $e) {
            Log::error("Mikrotik connection failed for {$this->router->name}: " . $e->getMessage());
            
            $this->router->update(['status' => 'down']);
            
            // Critical Alert
            $msg = "🚨 *CRITICAL: ROUTER DOWN*\n\n" .
                   "📡 *Router:* {$this->router->name}\n" .
                   "🌍 *IP:* {$this->router->ip_address}\n" .
                   "❌ *Error:* {$e->getMessage()}\n\n" .
                   "🤖 *Sender:* NOC Skynet\n" .
                   "⚠️ _Disclaimer: This is an automatic message._";

            $this->triggerAlert($msg, $whatsAppService);
        }
    }

    protected function simulateHealthCheck(WhatsAppService $whatsAppService)
    {
        // ... (keep existing logic) ...
        // 90% chance of being normal
        $rand = rand(1, 100);
        
        if ($rand <= 90) {
            $cpu = rand(1, 50);
            $freeMem = rand(50000000, 100000000); // 50MB - 100MB
            $temperature = rand(30, 55);
        } else {
             // Simulate danger
            $cpu = rand(91, 100);
            $freeMem = rand(1000000, 5000000); // 1MB - 5MB
            $temperature = rand(75, 95);
        }
        
        $this->router->update([
            'status' => 'up',
            'cpu_load' => $cpu,
            'temperature' => $temperature,
            'free_memory' => $freeMem,
            'total_memory' => 128000000, // 128MB
            'last_seen' => now(),
        ]);
        
        $this->checkThresholds($cpu, $freeMem, $temperature, $whatsAppService);
    }
    
    protected function checkThresholds($cpu, $freeMem, $temperature, $whatsAppService)
    {
        $issues = [];
        
        if ($cpu >= 90) {
            $issues[] = "🔥 *High CPU Load:* {$cpu}%";
        }

        if ($temperature >= 75) {
            $issues[] = "🌡️ *High Temp:* {$temperature}°C";
        }
        
        // Alert if free memory < 10MB
        if ($freeMem < 10 * 1024 * 1024) {
            $mb = round($freeMem / 1024 / 1024, 2);
            $issues[] = "💾 *Low Memory:* {$mb} MB free";
        }
        
        if (! empty($issues)) {
            $msg = "⚠️ *ROUTER WARNING*\n\n" .
                   "📡 *Router:* {$this->router->name}\n" . 
                   implode("\n", $issues) . "\n\n" .
                   "🤖 *Sender:* NOC Skynet\n" .
                   "⚠️ _Disclaimer: This is an automatic message._";
                   
            $this->triggerAlert($msg, $whatsAppService, [
                'cpu' => $cpu,
                'temperature' => $temperature,
                'free_memory' => $freeMem
            ]);
        }
    }
    
    protected function triggerAlert($message, $whatsAppService, $currentMetrics = [])
    {
        $shouldSend = false;
        $isEscalation = false;

        // 1. Check Time-based Rate Limiting (30 mins)
        if (! $this->router->last_alerted_at || $this->router->last_alerted_at->diffInMinutes(now()) >= 30) {
            $shouldSend = true;
        }

        // 2. Check Smart Escalation (If currently blocked by time, check if condition worsened)
        if (! $shouldSend && ! empty($currentMetrics) && ! empty($this->router->last_alert_values)) {
            $last = $this->router->last_alert_values;
            
            // Check Temperature Increase (+5)
            if (isset($currentMetrics['temperature']) && isset($last['temperature'])) {
                if ($currentMetrics['temperature'] >= $last['temperature'] + 5) {
                    $shouldSend = true;
                    $isEscalation = true;
                    $message = "📈 *ESCALATION ALERT*\n\n" . $message;
                }
            }
            
            // Check CPU Increase (+5)
            if (isset($currentMetrics['cpu']) && isset($last['cpu'])) {
                if ($currentMetrics['cpu'] >= $last['cpu'] + 5) {
                    $shouldSend = true;
                    $isEscalation = true;
                    $message = "📈 *ESCALATION ALERT*\n\n" . $message;
                }
            }
        }
        
        // Critical override (if message explicitly says CRITICAL, always send? 
        // Logic currently only handles escalation. Let's assume down status handles its own forcing logic via separate call if needed, 
        // but here we just respect the flag)
        
        if ($shouldSend) {
            $groupId = config('services.whatsapp.audit_group_id', env('WHATSAPP_AUDIT_GROUP_ID'));
            if ($groupId) {
                 $whatsAppService->sendMessageToGroup($groupId, $message);
                 
                 $this->router->update([
                     'last_alerted_at' => now(),
                     'last_alert_values' => $currentMetrics // Snapshot values
                 ]);
            }
        }
    }
}
