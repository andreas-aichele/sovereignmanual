<?php

use App\Http\Controllers\MagazineController;
use App\Http\Controllers\SitemapController;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

Route::get('/', [MagazineController::class, 'index'])->name('magazine.index');
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('sitemap-{page}.xml', [SitemapController::class, 'page'])->whereNumber('page')->name('sitemap.page');
Route::get('{locale}', [MagazineController::class, 'switchLocale'])
    ->whereIn('locale', Locales::supported())
    ->name('magazine.localized.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::get('{locale}/{category}/{slug}', [MagazineController::class, 'show'])
    ->whereIn('locale', Locales::supported())
    ->name('magazine.localized.show');
Route::get('{locale}/{category}', [MagazineController::class, 'category'])
    ->whereIn('locale', Locales::supported())
    ->name('magazine.localized.category');

Route::get('{category}/{slug}', [MagazineController::class, 'show'])->name('magazine.show');
Route::get('{category}', [MagazineController::class, 'category'])->name('magazine.category');
