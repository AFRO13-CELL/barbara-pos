<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Checks every minute whether a daily/weekly backup is due (see Backups page for the schedule).
Schedule::command('boutique:backup-auto')->everyMinute()->withoutOverlapping();
