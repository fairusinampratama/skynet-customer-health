<?php

namespace App\Jobs;

use App\Services\Reports\DailyErrorReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDailyErrorReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function handle(DailyErrorReportService $dailyErrorReportService): void
    {
        Log::info('Job Started: SendDailyErrorReportJob');
        
        try {
            $result = $dailyErrorReportService->send();
            Log::info($result->message);
            
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
