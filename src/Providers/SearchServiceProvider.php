<?php

declare(strict_types=1);

/**
 * Asyntai AI Search for Bagisto.
 *
 * The handshake, in order:
 *   1. "Connect" calls this package's own controller. PHP makes a preview
 *      secret and a feed token and parks them at Asyntai under a one-time
 *      state. No account exists yet, so nothing is linked to anybody.
 *   2. The browser opens the Asyntai sign-in window with that same state.
 *   3. PHP polls Asyntai for the state. Once the owner has signed in, the
 *      answer carries their site id.
 *
 * The poll runs through this store's own controller and NOT as a script tag
 * pointed at Asyntai, so no code from another server ever executes inside
 * the admin.
 */

namespace Asyntai\Search\Providers;

use Asyntai\Search\Http\Middleware\InjectSearchBar;
use Asyntai\Search\State;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/admin-menu.php', 'menu.admin');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin-routes.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/shop-routes.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'asyntai-search');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'asyntai-search');

        // Added once EVERY provider has booted. Composer discovers this
        // package before the application's own providers, and Bagisto's set
        // up the `web` group after us; a middleware pushed too early is
        // simply not there any more by the time a page is served.
        $this->app->booted(function () {
            /** @var Router $router */
            $router = $this->app['router'];
            $router->pushMiddlewareToGroup('web', InjectSearchBar::class);
        });

        // Re-ask Asyntai whether the bar may render, every ten minutes where
        // the store runs the scheduler...
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(function () {
                if (State::siteId() !== '') {
                    State::refreshIfStale();
                }
            })->everyTenMinutes()->name('asyntai-search-status')->withoutOverlapping();
        });

        // ...and after a page has been sent where it does not. Under PHP-FPM
        // the connection is handed back before this runs; under mod_php it
        // stays open, which is why State::refreshFromSite() lets only one
        // shopper pay for a slow answer rather than all of them.
        if (! $this->app->runningInConsole()) {
            $this->app->terminating(function () {
                $this->refreshAfterResponse();
            });
        }
    }

    private function refreshAfterResponse(): void
    {
        try {
            if (State::siteId() === '' || InjectSearchBar::isProbeRequest()) {
                return;
            }

            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }

            $request = $this->app['request'];
            $admin = trim((string) config('app.admin_url', 'admin'), '/');

            if ($request->is($admin) || $request->is($admin . '/*')) {
                State::refreshIfStale();
            } else {
                State::refreshFromSite();
            }
        } catch (\Throwable $e) {
            // Nothing here may ever surface to a shopper: the page has
            // already been sent, and this is bookkeeping.
        }
    }
}
