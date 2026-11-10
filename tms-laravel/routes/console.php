<?php

use App\Models\Payment;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| Monthly tuition bills.
|   php artisan tms:bill-month            -> this month
|   php artisan tms:bill-month 2026-12    -> a specific month
| Safe to run more than once: a registration is never billed twice for the same month.
*/
Artisan::command('tms:bill-month {period? : Billing month as YYYY-MM (default: this month)}', function () {
    $period = $this->argument('period') ?? Payment::currentPeriod();

    if (! Payment::isValidPeriod($period)) {
        $this->error('Use the format YYYY-MM, e.g. 2026-12.');

        return 1;
    }

    $count = Payment::generateMonthlyBills($period);
    $this->info("Created $count tuition bill(s) for ".Payment::periodLabel($period).'.');

    return 0;
})->purpose('Create the monthly tuition bills for every active registration');

// Bills the current month automatically while the Laravel scheduler is running (serve.bat starts it;
// on a server use cron / Windows Task Scheduler with "php artisan schedule:run" every minute).
// It runs every hour rather than only on the 1st, so a month is never missed if the PC was off -
// a registration is never billed twice for the same month.
Schedule::command('tms:bill-month')->hourly()->withoutOverlapping();
