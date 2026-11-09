<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SRS NFR8: enforce the retention period daily. This only runs when the
// Laravel scheduler is active, through a cron entry calling `schedule:run` or a
// long-running `schedule:work`. On a development machine with neither, nothing
// is deleted automatically; run `php artisan regradar:prune-analyses` to see
// what would be removed.
Schedule::command('regradar:prune-analyses --force')->dailyAt('03:00');
