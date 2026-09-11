<?php

declare(strict_types=1);

/**
 * Asyntai AI Search for Bagisto: the catalogue feed.
 *
 * Deliberately outside the `web` group. The feed is read by Asyntai's server,
 * not by a browser, so it needs no session, no CSRF token and no theme. Every
 * call proves itself with a signature instead; see Feed.
 */

use Asyntai\Search\Http\Controllers\Shop\FeedController;
use Illuminate\Support\Facades\Route;

Route::get('asyntai-search/feed', [FeedController::class, 'index'])
    ->name('shop.asyntai_search.feed');
