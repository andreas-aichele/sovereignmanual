<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:ideate-magazine-topics --count=1')
    ->weeklyOn(1, '08:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(60);

Schedule::command('app:ideate-magazine-topics --count=1')
    ->weeklyOn(4, '08:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(60);

Schedule::command('app:generate-due-magazine-posts')
    ->weeklyOn(1, '08:10')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(30);

Schedule::command('app:generate-due-magazine-posts')
    ->weeklyOn(4, '08:10')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(30);

Schedule::command('app:review-due-magazine-posts')
    ->dailyAt('03:15')
    ->withoutOverlapping(60);
