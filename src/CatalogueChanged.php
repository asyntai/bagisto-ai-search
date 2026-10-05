<?php

declare(strict_types=1);

namespace Asyntai\Search;

/**
 * Tell Asyntai that the catalogue changed, so it reads it again now.
 *
 * Without this Asyntai re-reads the catalogue once a day, and a product the
 * merchant has just disabled or deleted stays in the search results until
 * then. A save only marks the catalogue as changed; the one message goes out
 * after the response has been sent, so a mass action over five hundred
 * products is still one message, and the admin never waits for it.
 *
 * The message carries no product data. It only asks Asyntai to read the
 * signed feed again, which already hides disabled products.
 */
class CatalogueChanged
{
    private static bool $dirty = false;

    /**
     * Note that a product was created, saved, disabled or deleted.
     */
    public static function mark(): void
    {
        self::$dirty = true;
    }

    public static function pending(): bool
    {
        return self::$dirty;
    }

    /**
     * Send the one message, if anything changed. Never throws.
     */
    public static function flush(): void
    {
        if (! self::$dirty) {
            return;
        }

        self::$dirty = false;

        try {
            if (State::siteId() === '' || State::feedToken() === '' || ! State::feedEnabled()) {
                return;
            }

            $mode = State::backgroundMode();
            $console = app()->runningInConsole();

            // Under mod_php the admin's connection stays open until this
            // finishes, so a store with a real queue hands it to the queue.
            // Signed inside the job, so a busy queue cannot outlive the
            // timestamp.
            if ($mode === 'queue' && ! $console) {
                dispatch(function () {
                    CatalogueChanged::send(5);
                });

                return;
            }

            // FPM has already sent the page, and an import on the command
            // line has nobody waiting. Otherwise keep the wait short: the
            // answer is a bare 202.
            self::send(($mode === 'fpm' || $console) ? 5 : 2);
        } catch (\Throwable $e) {
            // The daily re-read still picks the change up.
        }
    }

    /**
     * Send the signed message now. Never throws.
     */
    public static function send(int $timeout): void
    {
        try {
            $siteId = State::siteId();
            $token = State::feedToken();

            if ($siteId === '' || $token === '') {
                return;
            }

            $ts = (string) time();

            State::httpPostJson(State::origin() . '/api/v1/store-feed/changed/', [
                'site_id' => $siteId,
                'ts'      => $ts,
                'sig'     => self::signature($siteId, $ts, $token),
            ], $timeout);
        } catch (\Throwable $e) {
            // The daily re-read still picks the change up.
        }
    }

    public static function signature(string $siteId, string $ts, string $token): string
    {
        return hash_hmac('sha256', 'changed:' . $siteId . ':' . $ts, $token);
    }
}
