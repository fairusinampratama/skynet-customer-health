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
        $date = Carbon::yesterday();
        $formattedDate = $date->format('Y-m-d');
        
        $this->info("Generating report for {$formattedDate}...");

        // 1. Fetch data
        // 1. Fetch data
        // We fetch customers who are currently marked as 'offline' or 'down'
        // Since we don't have historical HealthCheck records, we use the current status as a proxy for "needs fix"
        $customers = Customer::whereIn('status', ['offline', 'down', 'unstable'])
            ->where('is_isolated', false)
            ->with(['area', 'healthChecks' => function ($query) {
                $query->latest('checked_at')->limit(1);
            }])
            ->withCount(['healthChecks' => function ($query) {
                // Count issues from today
                $query->whereDate('checked_at', Carbon::today())
                      ->where('status', 'down');
            }])
            ->get();

        $this->info("Found {$customers->count()} customers with issues.");

        // 2. Generate PDF
        $pdf = Pdf::loadView('reports.daily_errors', [
            'date' => $formattedDate,
            'affectedCustomers' => $customers,
        ]);

        $fileName = "daily_error_report_{$formattedDate}.pdf";
        // Whatspie requires a PUBLIC URL. So we must save to the 'public' disk.
        // Ensure you have run 'php artisan storage:link'
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $disk->put("reports/{$fileName}", $pdf->output());

        $fileUrl = asset("storage/reports/{$fileName}");
        $this->info("PDF saved to public disk. URL: {$fileUrl}");

        // 3. Send via WhatsApp
        $groupId = $this->option('whatsapp_group_id') ?? config('services.whatsapp.audit_group_id', env('WHATSAPP_AUDIT_GROUP_ID'));

        if ($groupId) {
            $this->info("Sending to WhatsApp Group ID: {$groupId}");
            $sent = $whatsAppService->sendDocumentToGroup(
                $groupId,
                $fileUrl,
                "Daily Error Report for {$formattedDate}"
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
