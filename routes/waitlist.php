<?php

use App\Http\Controllers\WaitlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('waitlist')->as('waitlist.')->group(function (): void {
    Route::post('/', [WaitlistController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('store');

    Route::get('confirm/{waitlistSubscriber}/{token}', [WaitlistController::class, 'showConfirmation'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('confirm');
    Route::post('confirm/{waitlistSubscriber}/{token}', [WaitlistController::class, 'confirm'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('confirm.store');

    Route::get('unsubscribe/{waitlistSubscriber}/{token}', [WaitlistController::class, 'showUnsubscribe'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('unsubscribe');
    Route::post('unsubscribe/{waitlistSubscriber}/{token}', [WaitlistController::class, 'unsubscribe'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('unsubscribe.store');
});
