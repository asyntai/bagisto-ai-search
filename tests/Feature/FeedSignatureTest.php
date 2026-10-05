<?php

use Asyntai\Search\Feed;
use Asyntai\Search\State;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->connectStore();
});

it('should answer a request signed with the feed token', function () {
    // Act and Assert.
    $this->getFeed(['page' => '1', 'limit' => '10'])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonStructure(['ok', 'store', 'page', 'pages', 'total', 'products']);
});

it('should refuse a request with no signature', function () {
    // Act and Assert.
    getJson('asyntai-search/feed?page=1&ts=' . time())
        ->assertForbidden();
});

it('should refuse a request signed with another token', function () {
    // Act and Assert.
    $this->getFeed(['page' => '1'], null, 'not-the-feed-token')
        ->assertForbidden();
});

it('should refuse a signature made for different values', function () {
    // Arrange.
    $query = $this->signedFeedQuery(['page' => '1']);

    $query['page'] = '2';

    // Act and Assert.
    getJson('asyntai-search/feed?' . http_build_query($query))
        ->assertForbidden();
});

it('should refuse a request with no timestamp', function () {
    // Arrange.
    $query = $this->signedFeedQuery(['page' => '1']);

    unset($query['ts']);

    // Act and Assert.
    getJson('asyntai-search/feed?' . http_build_query($query))
        ->assertForbidden();
});

it('should refuse a timestamp older than the allowed clock skew', function () {
    // Act and Assert.
    $this->getFeed(['page' => '1'], time() - Feed::CLOCK_SKEW - 60)
        ->assertForbidden();
});

it('should refuse a timestamp too far in the future', function () {
    // Act and Assert.
    $this->getFeed(['page' => '1'], time() + Feed::CLOCK_SKEW + 60)
        ->assertForbidden();
});

it('should accept a timestamp inside the allowed clock skew', function () {
    // Act and Assert.
    $this->getFeed(['page' => '1'], time() - Feed::CLOCK_SKEW + 30)
        ->assertOk();
});

it('should refuse every request when the store is not connected', function () {
    // Arrange.
    State::forget('feed_token');

    // Act and Assert.
    $this->getFeed(['page' => '1'])
        ->assertForbidden();
});

it('should refuse every request when the merchant switched the feed off', function () {
    // Arrange.
    State::set('feed_enabled', '0');

    // Act and Assert.
    $this->getFeed(['page' => '1'])
        ->assertForbidden();
});
