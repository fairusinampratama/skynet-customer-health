<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\HealthCheck;
use App\Services\WhatsApp\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailyErrorReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-daily-error-report {--whatsapp_group_id= : The WhatsApp Group ID to send to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send a daily PDF report of down customers from yesterday';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $whatsAppService)
    {
        $date = Carbon::today();
        $dayName = $date->format('l');
        $formattedDate = $date->format('Y-m-d');
        $humanReadableDate = $date->format('l, d F Y');
        
        $hour = (int) now()->format('H');
        
        if ($hour < 10) {
            $reportTitle = "Morning Error Report";
            // Filter: Show all issues since midnight
        } elseif ($hour < 15) {
            $reportTitle = "Afternoon Error Report";
        } else {
            $reportTitle = "Evening Error Report";
        }

        $this->info("Generating {$reportTitle} for {$humanReadableDate}...");

        // 1. Fetch data using the reusable scope
        // This gets everyone with issues today
        $allCustomers = Customer::withIssuesOn($date)->get();
        
        // 2. Filter: Only keep customers with >= 5 minutes (checks) of downtime
        $customers = $allCustomers->filter(function ($customer) {
            return $customer->health_checks_count >= 5;
        });

        $this->info("Found {$allCustomers->count()} total issues. After filtering (< 5 mins): {$customers->count()} customers.");

        if ($customers->isEmpty()) {
            $this->info("No customers with significant downtime (> 5 mins). Skipping report.");
            // Optional: uncomment return to skip sending empty reports
            // return; 
        }

        // 3. Generate PDF
        $pdf = Pdf::loadView('reports.daily_errors', [
            'reportTitle' => $reportTitle,
            'date' => $humanReadableDate,
            'affectedCustomers' => $customers,
        ]);

        $safeTitle = \Illuminate\Support\Str::snake($reportTitle);
        $fileName = "{$safeTitle}_{$dayName}_{$formattedDate}_" . now()->format('H-i-s') . ".pdf";
        // Whatspie requires a PUBLIC URL. So we must save to the 'public' disk.
        // Ensure you have run 'php artisan storage:link'
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (!$disk->put("reports/{$fileName}", $pdf->output())) {
            $this->error("Failed to write PDF to disk!");
            return;
        }

        $fullPath = $disk->path("reports/{$fileName}");
        $this->info("PDF saved to: {$fullPath}");
        // This ensures WhatsApp sees the correct filename
        $fileUrl = route('reports.download', ['filename' => $fileName]);
        
        // Ensure the URL uses the APP_URL (localtunnel in this case)
        // route() helper usually does this, but forceRootUrl was not set, it might use localhost
        // For CLI commands, we rely on APP_URL in .env
        
        $this->info("PDF saved. Download URL: {$fileUrl}");

        // 3. Send via WhatsApp
        $groupId = $this->option('whatsapp_group_id') ?? config('services.whatsapp.audit_group_id', env('WHATSAPP_AUDIT_GROUP_ID'));

        if ($groupId) {
            $this->info("Sending to WhatsApp Group ID: {$groupId}");
            $sent = $whatsAppService->sendDocumentToGroup(
                $groupId,
                $fileUrl,
                "{$reportTitle} for {$humanReadableDate}\n\nSender: Skynet - NOC",
                $fileName
            );

            if ($sent) {
                $this->info("Report sent successfully.");
            } else {
                $this->error("Failed to send report via WhatsApp.");
            }
        } else {
            $this->warn("No WhatsApp Group ID provided or configured. Skipping send.");
        }
    }
}
