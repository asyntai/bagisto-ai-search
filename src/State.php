<?php

declare(strict_types=1);

/**
 * Asyntai AI Search for Bagisto.
 *
 * Everything this package remembers, and the calls it makes to Asyntai.
 *
 * Kept in its own table rather than in core_config, because the preview
 * secret and the feed token must never be printed into a configuration form,
 * and the site id and the cached status change without anybody touching a
 * settings screen.
 */

namespace Asyntai\Search;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class State
{
    public const TABLE = 'asyntai_search';

    public const DEFAULT_ORIGIN = 'https://asyntai.com';

    /** The keys a disconnect wipes. Settings stay: they are the owner's. */
    private const CONNECTION_KEYS = [
        'site_id', 'secret', 'feed_token', 'status', 'status_at',
        'state', 'state_at', 'account_email',
    ];

    /** @var bool|null Whether the table exists, remembered for this request. */
    private static ?bool $ready = null;

    /**
     * Where this store's PHP talks to Asyntai.
     *
     * Overridable from the environment so a staging copy can point at a test
     * server. There is deliberately no field for it: a settings screen that
     * lets somebody retype the server address is a settings screen that lets
     * somebody break their own store in a way support cannot see.
     */
    public static function origin(): string
    {
        $origin = (string) (getenv('ASYNTAI_SEARCH_ORIGIN') ?: env('ASYNTAI_SEARCH_ORIGIN', ''));

        return $origin !== '' ? rtrim($origin, '/') : self::DEFAULT_ORIGIN;
    }

    /**
     * What the SHOPPER'S browser downloads, which is not the same host as the
     * one above. Every other install route we document points at the widget
     * subdomain, so this one does too.
     */
    public static function scriptUrl(): string
    {
        $url = (string) (getenv('ASYNTAI_SEARCH_SCRIPT') ?: env('ASYNTAI_SEARCH_SCRIPT', ''));

        return $url !== '' ? $url : 'https://widget.asyntai.com/static/js/search-widget.js';
    }

    // -----------------------------------------------------------------
    // Storage
    // -----------------------------------------------------------------

    private static function ready(): bool
    {
        if (self::$ready === null) {
            try {
                self::$ready = Schema::hasTable(self::TABLE);
            } catch (\Throwable $e) {
                self::$ready = false;
            }
        }

        return self::$ready;
    }

    public static function get(string $name, string $default = ''): string
    {
        if (! self::ready()) {
            return $default;
        }

        try {
            $value = DB::table(self::TABLE)->where('name', $name)->value('value');
        } catch (\Throwable $e) {
            return $default;
        }

        return $value === null ? $default : (string) $value;
    }

    public static function set(string $name, string $value): bool
    {
        if (! self::ready()) {
            return false;
        }

        try {
            DB::table(self::TABLE)->updateOrInsert(['name' => $name], ['value' => $value]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function forget(string $name): void
    {
        if (! self::ready()) {
            return;
        }

        try {
            DB::table(self::TABLE)->where('name', $name)->delete();
        } catch (\Throwable $e) {
            // A row we could not delete is a row the next connect overwrites.
        }
    }

    public static function siteId(): string
    {
        return trim(self::get('site_id'));
    }

    /**
     * Forget the connection. The secret and the feed token go with it: a
     * secret kept after a disconnect can only ever sign a link nobody should
     * follow, and a token kept would let the feed keep answering for a store
     * that asked it to stop.
     */
    public static function disconnect(): void
    {
        foreach (self::CONNECTION_KEYS as $key) {
            self::forget($key);
        }

        self::afterChange();
    }

    // -----------------------------------------------------------------
    // The owner's settings
    // -----------------------------------------------------------------

    /**
     * 'replace'  take the place of the search box the theme already shows
     * 'manual'   render only where the theme puts <div data-asyntai-search>
     */
    public static function placement(): string
    {
        return self::get('placement') === 'manual' ? 'manual' : 'replace';
    }

    public static function selector(): string
    {
        return trim(self::get('selector'));
    }

    public static function accent(): string
    {
        return trim(self::get('accent'));
    }

    public static function placeholder(): string
    {
        return trim(self::get('placeholder'));
    }

    /**
     * Whether Asyntai may read the catalogue. On by default: live prices and
     * stock are the reason to run this on a shop at all.
     */
    public static function feedEnabled(): bool
    {
        return self::get('feed_enabled', '1') !== '0';
    }

    public static function feedToken(): string
    {
        return trim(self::get('feed_token'));
    }

    /**
     * The address Asyntai reads the catalogue from.
     */
    public static function feedUrl(): string
    {
        return self::siteUrl() . '/asyntai-search/feed';
    }

    // -----------------------------------------------------------------
    // The one question we ask Asyntai
    // -----------------------------------------------------------------

    /**
     * The last answer, or null when there has never been one.
     */
    public static function status(): ?array
    {
        $raw = self::get('status');

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * May the bar render on the shop right now?
     *
     * FALSE while we have never had an answer, on purpose. A bar that cannot
     * answer is worse than no bar, because in replace mode it has hidden the
     * theme's own search box behind it.
     */
    public static function enabled(): bool
    {
        $status = self::status();

        return $status !== null && ! empty($status['enabled']);
    }

    /**
     * Re-ask Asyntai and store the answer.
     *
     * Returns the answer, or null when the call failed, in which case the
     * previous answer is kept: a network blip must not switch a working
     * store's search off.
     */
    public static function refresh(int $timeout = 10): ?array
    {
        $siteId = self::siteId();

        if ($siteId === '') {
            self::forget('status');
            self::forget('status_at');

            return null;
        }

        $wasEnabled = self::enabled();

        $body = self::httpGet(
            self::origin() . '/api/v1/search-widget/status/?widget_id=' . rawurlencode($siteId),
            $timeout
        );

        if ($body === null) {
            return null;
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded) || ! array_key_exists('enabled', $decoded)) {
            return null;
        }

        self::set('status', (string) json_encode($decoded));
        self::set('status_at', (string) time());

        if ($wasEnabled !== ! empty($decoded['enabled'])) {
            self::afterChange();
        }

        return $decoded;
    }

    /**
     * Refresh only when the stored answer is older than Asyntai asked us to
     * keep it. For the settings screen, where somebody is waiting and wants
     * current information.
     */
    public static function refreshIfStale(): ?array
    {
        $status = self::status();

        if ($status === null) {
            return self::refresh();
        }

        $age = time() - (int) self::get('status_at', '0');

        return $age >= self::maxAge($status) ? self::refresh() : $status;
    }

    /**
     * The same thing, from an ordinary page on the shop.
     *
     * The Laravel scheduler only runs where somebody set up a cron job, and
     * many stores have none. So the refresh also rides on ordinary traffic,
     * and that makes the cost of a slow answer everybody's problem. Two rules
     * keep it small.
     *
     * The stamp is written BEFORE the call, not after. Otherwise a server that
     * accepts the connection and then says nothing makes EVERY shopper wait
     * the full timeout, because none of them ever gets to record an attempt.
     * With the stamp claimed first, one shopper waits and the rest sail past.
     *
     * And the timeout is short. On the settings screen ten seconds is
     * patience; on a shopper's page it is a page nobody waits for.
     */
    public static function refreshFromSite(): void
    {
        $status = self::status();
        $age = time() - (int) self::get('status_at', '0');

        if ($status !== null && $age < self::maxAge($status)) {
            return;
        }

        // Claim the attempt first. If the call then fails, the next check is
        // a whole interval away rather than on the very next page view.
        self::set('status_at', (string) time());
        self::refresh(4);
    }

    private static function maxAge(array $status): int
    {
        $maxAge = isset($status['cache_seconds']) ? (int) $status['cache_seconds'] : 3600;

        return $maxAge < 30 ? 30 : $maxAge;
    }

    /**
     * A sentence for the settings screen.
     *
     * Known reasons are worded here, in the package. An unknown reason falls
     * back to whatever Asyntai sent, which is what lets a new reason appear
     * without a new release of this package.
     */
    public static function message(?array $status): string
    {
        if ($status === null) {
            return trans('asyntai-search::app.admin.status.not_connected');
        }

        if (! empty($status['enabled'])) {
            $products = (int) ($status['products'] ?? 0);

            // A store with nothing in its catalogue yet is still searchable,
            // and "searching 0 products" would read as a fault.
            if ($products < 1) {
                return trans('asyntai-search::app.admin.status.live_pages');
            }

            return trans('asyntai-search::app.admin.status.live_products', [
                'count' => number_format($products),
            ]);
        }

        switch ($status['reason'] ?? '') {
            case 'plan':
                return trans('asyntai-search::app.admin.status.plan');
            case 'limit':
                return trans('asyntai-search::app.admin.status.limit');
            case 'no_products':
                return trans('asyntai-search::app.admin.status.reading');
            case 'unknown_widget':
                return trans('asyntai-search::app.admin.status.unknown_widget');
        }

        return ! empty($status['message'])
            ? strip_tags((string) $status['message'])
            : trans('asyntai-search::app.admin.status.off');
    }

    // -----------------------------------------------------------------
    // The owner's own bar, on a link
    // -----------------------------------------------------------------

    /**
     * The address of this store's own search bar, carrying proof that this
     * store asked for it.
     *
     * Signed rather than relying on a session: the owner is signed in to
     * Bagisto, not necessarily to Asyntai, and a link that lands on a sign-in
     * form is a link nobody follows. Empty without a secret, which hides the
     * panel rather than offering a door that does not open.
     */
    public static function previewUrl(): string
    {
        $siteId = self::siteId();
        $secret = self::get('secret');

        if ($siteId === '' || $secret === '') {
            return '';
        }

        $expiry = time() + 1500;

        return self::origin() . '/ai-search-bar/preview/?' . http_build_query([
            'widget_id' => $siteId,
            'token'     => $expiry . '.' . hash_hmac('sha256', $siteId . ':' . $expiry, $secret),
        ]);
    }

    // -----------------------------------------------------------------
    // The store itself
    // -----------------------------------------------------------------

    /**
     * The public address of this store.
     *
     * The default channel's hostname where the owner set one, because that is
     * where shoppers arrive; APP_URL otherwise. Bagisto accepts a hostname
     * with or without a scheme, so a bare one borrows the scheme from APP_URL.
     */
    public static function siteUrl(): string
    {
        $appUrl = rtrim((string) config('app.url', ''), '/');
        $host = '';

        try {
            $channel = core()->getDefaultChannel();
            $host = $channel ? trim((string) $channel->hostname) : '';
        } catch (\Throwable $e) {
            $host = '';
        }

        if ($host === '') {
            return $appUrl;
        }

        if (! preg_match('#^https?://#i', $host)) {
            $scheme = (stripos($appUrl, 'https://') === 0) ? 'https://' : 'http://';
            $host = $scheme . $host;
        }

        return rtrim($host, '/');
    }

    /**
     * Something on the shop changed what its pages carry: the script tag
     * appears or disappears. Bagisto keeps rendered pages in its response
     * cache, so those pages are thrown away here, or a disconnected store
     * would keep serving the bar until the cache expired on its own.
     */
    public static function afterChange(): void
    {
        try {
            if (class_exists(\Spatie\ResponseCache\Facades\ResponseCache::class)) {
                \Spatie\ResponseCache\Facades\ResponseCache::clear();
            }
        } catch (\Throwable $e) {
            // A cache we could not clear expires on its own.
        }
    }

    // -----------------------------------------------------------------
    // HTTP
    // -----------------------------------------------------------------

    /**
     * Body of a GET, or null on any failure.
     */
    public static function httpGet(string $url, int $timeout = 10, array $headers = []): ?string
    {
        try {
            $response = Http::timeout($timeout)
                ->connectTimeout($timeout)
                ->withHeaders(array_merge(['Accept' => 'application/json'], $headers))
                ->get($url);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return (string) $response->body();
    }

    /**
     * Body of a JSON POST, or null on any failure.
     */
    public static function httpPostJson(string $url, array $payload, int $timeout = 15): ?string
    {
        try {
            $response = Http::timeout($timeout)
                ->connectTimeout($timeout)
                ->acceptJson()
                ->post($url, $payload);
        } catch (\Throwable $e) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return (string) $response->body();
    }
}
