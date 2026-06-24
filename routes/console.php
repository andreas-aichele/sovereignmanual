<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:ideate-magazine-topics --count=1')
    ->weeklyOn(1, '08:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(60);

Schedule::command('app:ideate-magazine-topics --count=1')
    ->weeklyOn(4, '08:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(60);

Schedule::command('app:ideate-news-topics --count=1')
    ->weeklyOn(3, '08:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(60);

Schedule::command('app:generate-due-magazine-posts')
    ->weeklyOn(1, '08:10')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(30);

Schedule::command('app:generate-due-magazine-posts')
    ->weeklyOn(3, '08:10')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(30);

Schedule::command('app:generate-due-magazine-posts')
    ->weeklyOn(4, '08:10')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping(30);
