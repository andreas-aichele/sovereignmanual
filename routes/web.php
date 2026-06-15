<?php

use App\Http\Controllers\MagazineController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MagazineController::class, 'index'])->name('magazine.index');
Route::get('sitemap.xml', [MagazineController::class, 'sitemap'])->name('sitemap');
Route::get('/en', [MagazineController::class, 'switchLocale'])->defaults('locale', 'en')->name('magazine.en.index');
Route::get('/de', [MagazineController::class, 'switchLocale'])->defaults('locale', 'de')->name('magazine.de.index');
Route::get('magazine/{slug}', [MagazineController::class, 'show'])->defaults('locale', 'en')->name('magazine.show');
Route::get('de/magazine/{slug}', [MagazineController::class, 'show'])->defaults('locale', 'de')->name('magazine.de.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
