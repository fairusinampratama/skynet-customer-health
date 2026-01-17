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
        
        $this->info("Generating report for {$humanReadableDate}...");

        // 1. Fetch data using the reusable scope
        $customers = Customer::withIssuesOn($date)->get();

        $this->info("Found {$customers->count()} customers with issues.");

        // 2. Generate PDF
        $pdf = Pdf::loadView('reports.daily_errors', [
            'date' => $humanReadableDate,
            'affectedCustomers' => $customers,
        ]);

        $fileName = "Daily_Error_Report_{$dayName}_{$formattedDate}_" . now()->format('H-i-s') . ".pdf";
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
                "Daily Error Report for {$humanReadableDate}\n\nSender: Skynet - NOC",
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
