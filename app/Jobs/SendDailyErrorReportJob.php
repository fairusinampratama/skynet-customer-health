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
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Job Started: SendDailyErrorReportJob');
        
        try {
            // We reuse the existing artisan command logic
            Log::info('Dispatching Artisan Command: app:send-daily-error-report');
            
            $exitCode = Artisan::call('app:send-daily-error-report');
            
            Log::info('Job Finished: SendDailyErrorReportJob', ['exit_code' => $exitCode]);
            
            if ($exitCode !== 0) {
                 Log::error('Job FAILED: Artisan command returned non-zero exit code.', ['exit_code' => $exitCode]);
                 throw new \Exception("Artisan command failed with exit code $exitCode");
            }
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
