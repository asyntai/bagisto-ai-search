<?php

declare(strict_types=1);

/**
 * Asyntai AI Search for Bagisto: puts the widget script on shop pages.
 *
 * Nothing is printed at all unless Asyntai has said yes. That is the one rule
 * this package exists to enforce: a search box that cannot answer is worse
 * than no search box, because in replace mode it has hidden the theme's own
 * search behind it.
 */

namespace Asyntai\Search\Http\Middleware;

use Asyntai\Search\State;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectSearchBar
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->isAdminRoute($request) || $this->isApiRoute($request)) {
            return $response;
        }

        // Our own probe of the front page, which exists to measure the theme
        // and not ourselves.
        if (self::isProbeRequest()) {
            return $response;
        }

        if (! $this->isHtml($response)) {
            return $response;
        }

        $siteId = State::siteId();

        if ($siteId === '' || ! State::enabled()) {
            return $response;
        }

        $content = (string) $response->getContent();

        if (str_contains($content, 'data-asyntai-id')) {
            return $response;
        }

        $position = stripos($content, '</head>');

        if ($position === false) {
            return $response;
        }

        // In the head rather than after the load event, unlike our chat
        // widget: this bar sits in the header, and a late swap means the
        // shopper watches the theme's search box get replaced in front of
        // them.
        $content = substr($content, 0, $position) . self::scriptTag($siteId) . substr($content, $position);

        $response->setContent($content);

        return $response;
    }

    /**
     * The tag itself. Public so a theme that wants the bar somewhere of its
     * own choosing can print the same tag.
     */
    public static function scriptTag(string $siteId): string
    {
        $attributes = [
            'src'             => State::scriptUrl(),
            'data-asyntai-id' => $siteId,
        ];

        // The widget calls asyntai.com unless told otherwise. A store pointed
        // at a staging copy of Asyntai has to say so on the tag as well, or
        // its PHP and its shoppers' browsers would talk to different servers.
        if (State::origin() !== State::DEFAULT_ORIGIN) {
            $attributes['data-api-base'] = State::origin();
        }

        if (State::placement() === 'replace') {
            $selector = State::selector();
            $attributes['data-replace'] = $selector !== '' ? $selector : 'auto';
        }

        if (State::accent() !== '') {
            $attributes['data-accent'] = State::accent();
        }

        if (State::placeholder() !== '') {
            $attributes['data-placeholder'] = State::placeholder();
        }

        $html = '<script async';

        foreach ($attributes as $name => $value) {
            $html .= ' ' . $name . '="' . e($value) . '"';
        }

        // No cache-busting: the widget is served and versioned by Asyntai,
        // so a query string of ours would only break the browser cache every
        // time this package updates.
        return $html . '></script>';
    }

    public static function isProbeRequest(): bool
    {
        return isset($_SERVER['HTTP_X_ASYNTAI_PROBE']) && $_SERVER['HTTP_X_ASYNTAI_PROBE'] === '1';
    }

    private function isHtml(Response $response): bool
    {
        $contentType = (string) $response->headers->get('Content-Type', '');

        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return false;
        }

        // A streamed or binary response has no content to look at.
        return is_string($response->getContent()) && $response->getContent() !== '';
    }

    private function isAdminRoute(Request $request): bool
    {
        $admin = trim((string) config('app.admin_url', 'admin'), '/');

        return $request->is($admin) || $request->is($admin . '/*');
    }

    private function isApiRoute(Request $request): bool
    {
        return $request->is('api') || $request->is('api/*');
    }
}
