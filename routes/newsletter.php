<?php

use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;

Route::prefix('newsletter')->as('newsletter.')->group(function (): void {
    Route::post('/', [NewsletterController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('store');

    Route::get('confirm/{newsletterSubscriber}/{token}', [NewsletterController::class, 'showConfirmation'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('confirm');
    Route::post('confirm/{newsletterSubscriber}/{token}', [NewsletterController::class, 'confirm'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('confirm.store');

    Route::get('unsubscribe/{newsletterSubscriber}/{token}', [NewsletterController::class, 'showUnsubscribe'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('unsubscribe');
    Route::post('unsubscribe/{newsletterSubscriber}/{token}', [NewsletterController::class, 'unsubscribe'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('unsubscribe.store');
});
