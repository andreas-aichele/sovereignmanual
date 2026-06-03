<?php

use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BlogController::class, 'index'])->name('blog.index');
Route::get('/de', [BlogController::class, 'index'])->defaults('locale', 'de')->name('blog.de.index');
Route::redirect('blog', '/');
Route::redirect('de/blog', '/de');
Route::get('blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('de/blog/{slug}', [BlogController::class, 'show'])->defaults('locale', 'de')->name('blog.de.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
