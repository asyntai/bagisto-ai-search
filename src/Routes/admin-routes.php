<?php

declare(strict_types=1);

/**
 * Asyntai AI Search for Bagisto: admin routes.
 *
 * Every call the settings screen makes lands here, on this store's own
 * controller. Nothing is fetched from Asyntai as a script, so no code from
 * another server runs inside the admin.
 */

use Asyntai\Search\Http\Controllers\Admin\SearchController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => config('app.admin_url', 'admin') . '/asyntai-search',
    'middleware' => ['web', 'admin'],
], function () {
    Route::get('', [SearchController::class, 'index'])->name('admin.asyntai_search.index');
    Route::post('prepare', [SearchController::class, 'prepare'])->name('admin.asyntai_search.prepare');
    Route::post('poll', [SearchController::class, 'poll'])->name('admin.asyntai_search.poll');
    Route::post('finish', [SearchController::class, 'finish'])->name('admin.asyntai_search.finish');
    Route::post('disconnect', [SearchController::class, 'disconnect'])->name('admin.asyntai_search.disconnect');
    Route::post('refresh', [SearchController::class, 'refresh'])->name('admin.asyntai_search.refresh');
    Route::post('settings', [SearchController::class, 'saveSettings'])->name('admin.asyntai_search.settings');
});
