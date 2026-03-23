<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Reports\CustomerStatusImageReportService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Console\Command;

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
    protected $description = 'Generate and send a daily JPG report of customer status';

    /**
     * Execute the console command.
     */
    public function handle(
        WhatsAppService $whatsAppService,
        CustomerStatusImageReportService $reportService
    ): void {
        if (!Setting::getValue('daily_report_enabled', true)) {
            $this->warn('Daily Error Report is disabled in settings. Skipping.');
            return;
        }

        $report = $reportService->generateAndStoreReport();
        $this->info("JPG saved. Download URL: {$report['file_url']}");

        $groupId = $this->option('whatsapp_group_id') ?? config('services.whatsapp.audit_group_id', env('WHATSAPP_AUDIT_GROUP_ID'));
        if (!$groupId) {
            $this->warn('No WhatsApp Group ID provided or configured. Skipping send.');
            return;
        }

        $this->info("Sending to WhatsApp Group ID: {$groupId}");
        $sent = $whatsAppService->sendDocumentToGroup(
            $groupId,
            $report['file_url'],
            $reportService->buildWhatsAppCaption($report),
            $report['file_name']
        );

        if ($sent) {
            $this->info('Report sent successfully.');
            return;
        }

        $this->error('Failed to send report via WhatsApp.');
    }
}
