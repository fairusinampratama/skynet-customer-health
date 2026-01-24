<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SendDailyErrorReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function handle(\App\Services\WhatsApp\WhatsAppService $whatsAppService): void
    {
        Log::info('Job Started: SendDailyErrorReportJob (Direct Logic)');
        
        try {
            // Check if report sending is enabled
            if (!\App\Models\Setting::getValue('daily_report_enabled', true)) {
                Log::warning('Daily Error Report is disabled in settings. Skipping.');
                return;
            }

            Log::info('Preparing data...');
            $date = \Carbon\Carbon::today();
            $dayName = $date->format('l');
            $formattedDate = $date->format('Y-m-d');
            $humanReadableDate = $date->format('l, d F Y');
            $reportTitle = "Error Report - " . now()->format('H:i');

            Log::info('Fetching critical customers...');
            // Fetch data
            $customers = \App\Models\Customer::criticallyDown()->with('area')->get();
            Log::info("Found {$customers->count()} critical issues.");

            if ($customers->isEmpty()) {
                Log::info("No customers with significant downtime. Skipping report.");
                return; 
            }

            Log::info('Generating PDF...');
            // Generate PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.daily_errors', [
                'reportTitle' => $reportTitle,
                'date' => $humanReadableDate,
                'affectedCustomers' => $customers,
            ]);

            $safeTitle = \Illuminate\Support\Str::snake($reportTitle);
            $fileName = "{$safeTitle}_{$dayName}_{$formattedDate}_" . now()->format('H-i-s') . ".pdf";
            
            Log::info('Saving PDF to disk...');
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            if (!$disk->put("reports/{$fileName}", $pdf->output())) {
                throw new \Exception("Failed to write PDF to disk!");
            }

            $fullPath = $disk->path("reports/{$fileName}");
            // Use config app.url if available to ensure correct domain in queue context
            // But route() should work if configured correctly.
            // fallback to relative if needed, but WhatsApp needs public URL.
            $fileUrl = route('reports.download', ['filename' => $fileName]);
            
            Log::info("PDF saved. URL: {$fileUrl}");

            // Send via WhatsApp
            $groupId = config('services.whatsapp.audit_group_id', env('WHATSAPP_AUDIT_GROUP_ID'));

            if ($groupId) {
                Log::info("Sending to WhatsApp Group ID: {$groupId}");
                $sent = $whatsAppService->sendDocumentToGroup(
                    $groupId,
                    $fileUrl,
                    "📊 *{$reportTitle}*\n" .
                    "📅 {$humanReadableDate}\n" .
                    "📉 *Issues Found:* {$customers->count()} Customers\n\n" .
                    "📎 _See attached PDF for details._\n\n" .
                    "🤖 *Sender:* NOC Skynet\n" .
                    "⚠️ _Disclaimer: This is an automatic message._",
                    $fileName
                );

                if ($sent) {
                    Log::info("Report sent successfully.");
                } else {
                    throw new \Exception("Failed to send report via WhatsApp Service.");
                }
            } else {
                Log::warning("No WhatsApp Group ID configured.");
            }
            
            Log::info('Job Finished: SendDailyErrorReportJob');

        } catch (\Throwable $e) {
            Log::error('Job CRASHED: SendDailyErrorReportJob', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; 
        }
    }
}
