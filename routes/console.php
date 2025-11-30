<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Invoice Scheduling
|--------------------------------------------------------------------------
|
| These scheduled tasks handle automatic invoice generation and status
| updates for the billing system.
|
*/

// Generate monthly invoices on the 1st of each month at 6 AM
Schedule::command('invoices:generate-monthly')
    ->monthlyOn(1, '06:00')
    ->withoutOverlapping()
    ->onOneServer();

// Check for overdue invoices daily at midnight
Schedule::command('invoices:check-overdue')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->onOneServer();
