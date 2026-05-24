<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->defaults('locale', 'en')->name('home');
Route::get('/de', HomeController::class)->defaults('locale', 'de')->name('home.de');

Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('de/blog', [BlogController::class, 'index'])->defaults('locale', 'de')->name('blog.de.index');
Route::get('de/blog/{slug}', [BlogController::class, 'show'])->defaults('locale', 'de')->name('blog.de.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
