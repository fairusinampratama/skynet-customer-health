<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Services\Reports\CustomerStatusImageReportService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDailyErrorReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        WhatsAppService $whatsAppService,
        CustomerStatusImageReportService $reportService
    ): void {
        Log::info('Job Started: SendDailyErrorReportJob');

        try {
            if (!Setting::getValue('daily_report_enabled', true)) {
                Log::warning('Daily Error Report is disabled in settings. Skipping.');
                return;
            }

            $report = $reportService->generateAndStoreReport();
            Log::info("JPG saved. URL: {$report['file_url']}");

            $groupId = config('services.whatsapp.audit_group_id', env('WHATSAPP_AUDIT_GROUP_ID'));
            if (!$groupId) {
                Log::warning('No WhatsApp Group ID configured.');
                return;
            }

            $sent = $whatsAppService->sendDocumentToGroup(
                $groupId,
                $report['file_url'],
                $reportService->buildWhatsAppCaption($report),
                $report['file_name']
            );

            if (!$sent) {
                throw new \RuntimeException('Failed to send report via WhatsApp service.');
            }

            Log::info('Job Finished: SendDailyErrorReportJob');
        } catch (\Throwable $e) {
            Log::error('Job CRASHED: SendDailyErrorReportJob', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
