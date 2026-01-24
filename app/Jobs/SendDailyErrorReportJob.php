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
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Job Started: SendDailyErrorReportJob');
        
        try {
            // We reuse the existing artisan command logic
            // Ideally we would move logic to a Service, but calling Artisan is safe here
            // and ensures identical behavior to the CLI command.
            Artisan::call('app:send-daily-error-report');
            
            Log::info('Job Finished: SendDailyErrorReportJob');
        } catch (\Throwable $e) {
            Log::error('Job Failed: SendDailyErrorReportJob', ['error' => $e->getMessage()]);
            // Optionally we could re-throw to let the queue worker retry
            // throw $e; 
        }
    }
}
