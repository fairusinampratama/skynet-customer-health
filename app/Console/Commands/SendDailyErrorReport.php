<?php

namespace App\Console\Commands;

use App\Services\Reports\DailyErrorReportService;
use Illuminate\Console\Command;

class SendDailyErrorReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-daily-error-report
        {--whatsapp_group_id= : The WhatsApp Group ID to send to}
        {--test-fixture : Generate a report with local fixture data instead of querying the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send a daily PDF report of down customers from yesterday';

    /**
     * Execute the console command.
     */
    public function handle(DailyErrorReportService $dailyErrorReportService): int
    {
        try {
            $result = $dailyErrorReportService->send(
                groupId: $this->option('whatsapp_group_id'),
                useFixture: (bool) $this->option('test-fixture'),
            );
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->{$result->wasSkipped() ? 'warn' : 'info'}($result->message);

        if ($result->filePath) {
            $this->info("PDF saved to: {$result->filePath}");
        }

        return $result->wasSent() || $result->wasSkipped()
            ? self::SUCCESS
            : self::FAILURE;
    }
}
