<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('health:check')->everyMinute()->withoutOverlapping(5);
\Illuminate\Support\Facades\Schedule::command('app:send-daily-error-report')->dailyAt('08:00');
\Illuminate\Support\Facades\Schedule::command('app:send-daily-error-report')->dailyAt('12:30');
\Illuminate\Support\Facades\Schedule::command('app:send-daily-error-report')->dailyAt('19:00');
