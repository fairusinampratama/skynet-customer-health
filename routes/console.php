<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule a check every minute
\Illuminate\Support\Facades\Schedule::command('health:check')->everyMinute()->withoutOverlapping(5);

// Schedule reports every 2 hours from 08:00 to 00:00 (Midnight)
$hours = ['08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00', '00:00'];
foreach ($hours as $hour) {
    \Illuminate\Support\Facades\Schedule::command('app:send-daily-error-report')->dailyAt($hour);
}
