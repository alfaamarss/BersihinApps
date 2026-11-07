<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule: Generate waste deliveries automatically
Schedule::command('deliveries:generate --days=7')
    ->daily()
    ->at('00:00')
    ->description('Generate waste deliveries for the next 7 days');

// Schedule: Send schedule reminders every morning at 6 AM
Schedule::command('notifications:schedule-reminders')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->description('Send pickup schedule reminders to users');

// Schedule: Send billing reminders every day at 8 AM
Schedule::command('notifications:billing-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->description('Send billing reminders (H-3, H-2, H-1, due today, overdue)');
