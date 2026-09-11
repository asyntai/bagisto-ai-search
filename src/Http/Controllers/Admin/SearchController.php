<?php

declare(strict_types=1);

/**
 * Asyntai AI Search for Bagisto: the settings screen and the connect
 * handshake behind it.
 */

namespace Asyntai\Search\Http\Controllers\Admin;

use Asyntai\Search\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SearchController extends Controller
{
    /** How long a handshake state stays valid. */
    private const STATE_TTL = 900;

    public function index()
    {
        $siteId = State::siteId();
        $status = $siteId !== '' ? State::refreshIfStale() : null;

        $reason = $status['reason'] ?? '';

        if ($status !== null && ! empty($status['enabled'])) {
            $state = 'live';
        } elseif ($reason === 'no_products') {
            $state = 'setting_up';
        } elseif ($reason === 'plan' || $reason === 'limit') {
            $state = 'blocked';
        } else {
            $state = 'unknown';
        }

        return view('asyntai-search::admin.index', [
            'siteId'       => $siteId,
            'accountEmail' => State::get('account_email'),
            'status'       => $status,
            'state'        => $state,
            'message'      => $state === 'unknown'
                ? trans('asyntai-search::app.admin.status.unreachable')
                : State::message($status),
            'previewUrl'   => State::previewUrl(),
            'placement'    => State::placement(),
            'selector'     => State::selector(),
            'accent'       => State::accent(),
            'placeholder'  => State::placeholder(),
            'feedEnabled'  => State::feedEnabled(),
            'feedUrl'      => State::feedUrl(),
        ]);
    }

    /**
     * Park a fresh preview secret and feed token at Asyntai and hand the
     * browser the sign-in address.
     */
    public function prepare(Request $request): JsonResponse
    {
        $state = 'bg_' . bin2hex(random_bytes(12));

        // Made here and sent once, in this request body, which is the only
        // hop of the handshake that never passes through a browser. With it
        // this store can prove a preview belongs to it without depending on
        // a cookie surviving inside somebody else's frame.
        $secret = bin2hex(random_bytes(24));

        // The feed token is written BEFORE staging, and kept if staging fails:
        // Asyntai reads the catalogue the moment the owner signs in, and the
        // feed must already answer to the token Asyntai was given.
        $feedToken = bin2hex(random_bytes(24));
        State::set('feed_token', $feedToken);

        $body = State::httpPostJson(State::origin() . '/api/v1/wp-search/stage/', [
            'state'           => $state,
            'site_url'        => State::siteUrl(),
            'consumer_key'    => '',
            'consumer_secret' => '',
            'plugin_secret'   => $secret,
            'platform'        => 'bagisto',
            'product'         => 'search',
            'feed_token'      => State::feedEnabled() ? $feedToken : '',
            'feed_url'        => State::feedEnabled() ? State::feedUrl() : '',
        ]);

        if ($body === null) {
            return response()->json([
                'error' => trans('asyntai-search::app.admin.js.unreachable'),
            ], 502);
        }

        // Kept only after Asyntai accepted it, so a failed handshake cannot
        // leave this store signing tokens with a secret nobody knows.
        State::set('secret', $secret);
        State::set('state', $state);
        State::set('state_at', (string) time());

        $email = '';

        try {
            $admin = auth()->guard('admin')->user();
            $email = $admin ? (string) $admin->email : '';
        } catch (\Throwable $e) {
            $email = '';
        }

        $url = State::origin() . '/wp-auth?' . http_build_query([
            'state'    => $state,
            'site_url' => State::siteUrl(),
            'platform' => 'bagisto',
            // Which product brought them, kept apart from which platform:
            // this is what separates a search install from a chat install
            // in the signup numbers.
            'product'  => 'search',
            'wp_email' => $email,
            'lang'     => substr((string) app()->getLocale(), 0, 2),
        ]);

        return response()->json(['state' => $state, 'url' => $url]);
    }

    /**
     * Has the owner finished signing in?
     */
    public function poll(Request $request): JsonResponse
    {
        $state = trim((string) $request->input('state', ''));
        $stored = State::get('state');
        $startedAt = (int) State::get('state_at', '0');

        // Only the state this store just generated is ever polled, so a
        // guessed or replayed one cannot be used to read somebody else's
        // handshake.
        if ($state === '' || $state !== $stored) {
            return response()->json(['error' => 'Unknown handshake'], 400);
        }

        if (time() - $startedAt > self::STATE_TTL) {
            return response()->json([
                'error' => trans('asyntai-search::app.admin.js.expired'),
            ], 410);
        }

        $body = State::httpGet(State::origin() . '/api/v1/wp-search/connect-status/?state=' . rawurlencode($state));

        if ($body === null) {
            return response()->json(['ready' => false]);
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded) || empty($decoded['ready']) || empty($decoded['data']['site_id'])) {
            return response()->json(['ready' => false]);
        }

        return response()->json([
            'ready'         => true,
            'site_id'       => (string) $decoded['data']['site_id'],
            'account_email' => (string) ($decoded['data']['account_email'] ?? ''),
        ]);
    }

    /**
     * Remember the site id and ask Asyntai for a first status.
     */
    public function finish(Request $request): JsonResponse
    {
        $siteId = trim((string) $request->input('site_id', ''));

        // The id is ours, so its shape is known. Anything else is a caller
        // that did not come from the poll above.
        if ($siteId === '' || ! preg_match('/^[A-Za-z0-9_-]{6,64}$/', $siteId)) {
            return response()->json(['error' => 'Invalid site id'], 400);
        }

        State::set('site_id', $siteId);
        State::set('account_email', trim((string) $request->input('account_email', '')));
        State::forget('state');
        State::forget('state_at');
        State::refresh();
        State::afterChange();

        return response()->json(['ok' => true]);
    }

    public function disconnect(): JsonResponse
    {
        State::disconnect();

        return response()->json(['ok' => true]);
    }

    /**
     * Ask again now. For the settings screen's own button.
     */
    public function refresh(): JsonResponse
    {
        $status = State::refresh();

        return response()->json([
            'ok'      => $status !== null,
            'enabled' => $status !== null && ! empty($status['enabled']),
            'message' => State::message($status ?? State::status()),
        ]);
    }

    /**
     * The four things the owner chooses, plus the catalogue switch.
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $placement = (string) $request->input('placement', 'replace');
        State::set('placement', $placement === 'manual' ? 'manual' : 'replace');

        foreach (['selector', 'accent', 'placeholder'] as $key) {
            $value = trim((string) $request->input($key, ''));
            State::set($key, mb_substr($value, 0, 200));
        }

        $feed = $request->boolean('feed_enabled', true);
        State::set('feed_enabled', $feed ? '1' : '0');

        State::afterChange();

        return response()->json(['ok' => true]);
    }
}
