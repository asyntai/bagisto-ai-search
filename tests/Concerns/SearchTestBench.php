<?php

namespace Asyntai\Search\Tests\Concerns;

use Asyntai\Search\State;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

trait SearchTestBench
{
    /** The token the store and Asyntai share, for the length of one test. */
    public string $feedToken = 'test-feed-token-0123456789abcdef';

    public string $siteId = 'asyntai_test0001';

    /**
     * Connect this store to Asyntai. Nothing leaves the test: every call to
     * Asyntai is answered by Http::fake().
     */
    public function connectStore(): void
    {
        Http::fake([
            '*' => Http::response(['enabled' => true, 'reason' => 'ok', 'cache_seconds' => 600], 200),
        ]);

        State::set('site_id', $this->siteId);
        State::set('feed_token', $this->feedToken);
        State::set('feed_enabled', '1');
    }

    /**
     * The query string Asyntai sends: page, limit, ids and ts, signed in that
     * order with the feed token.
     */
    public function signedFeedQuery(array $values = [], ?int $ts = null, ?string $token = null): array
    {
        $query = [
            'page'  => $values['page'] ?? '',
            'limit' => $values['limit'] ?? '',
            'ids'   => $values['ids'] ?? '',
            'ts'    => (string) ($ts ?? time()),
        ];

        $signed = 'page=' . $query['page']
            . '&limit=' . $query['limit']
            . '&ids=' . $query['ids']
            . '&ts=' . $query['ts'];

        $query['sig'] = hash_hmac('sha256', $signed, $token ?? $this->feedToken);

        return array_filter($query, fn ($value) => $value !== '');
    }

    public function getFeed(array $values = [], ?int $ts = null, ?string $token = null): TestResponse
    {
        return $this->getJson('asyntai-search/feed?' . http_build_query(
            $this->signedFeedQuery($values, $ts, $token)
        ));
    }

    /**
     * The product with this id as the feed sends it, or null if the feed
     * leaves it out.
     */
    public function feedProduct(int $productId): ?array
    {
        $products = $this->getFeed(['ids' => (string) $productId])
            ->assertOk()
            ->json('products');

        foreach ($products as $product) {
            if ((int) $product['id'] === $productId) {
                return $product;
            }
        }

        return null;
    }
}
