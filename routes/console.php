<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:generate-due-blog-posts')
    ->hourly()
    ->withoutOverlapping(30);

Schedule::command('app:review-due-blog-posts')
    ->dailyAt('03:15')
    ->withoutOverlapping(60);
