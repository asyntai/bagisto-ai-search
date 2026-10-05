<?php

use Asyntai\Search\CatalogueChanged;
use Asyntai\Search\State;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Webkul\Faker\Helpers\Product as ProductFaker;

beforeEach(function () {
    $this->connectStore();

    // Product factories fire the save event; start every test clean.
    CatalogueChanged::flush();

    Http::fake(['*' => Http::response(['ok' => true], 202)]);
});

function changedMessages(): array
{
    return collect(Http::recorded())
        ->map(fn ($pair) => $pair[0])
        ->filter(fn (Request $request) => str_contains($request->url(), '/api/v1/store-feed/changed/'))
        ->values()
        ->all();
}

it('should mark the catalogue as changed when a product is saved', function () {
    // Arrange.
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    CatalogueChanged::flush();

    Http::fake(['*' => Http::response(['ok' => true], 202)]);

    // Act.
    Event::dispatch('catalog.product.update.after', $product);

    // Assert.
    expect(CatalogueChanged::pending())->toBeTrue();
});

it('should mark the catalogue as changed when a product is deleted', function () {
    // Act.
    Event::dispatch('catalog.product.delete.after', 1);

    // Assert.
    expect(CatalogueChanged::pending())->toBeTrue();
});

it('should send one signed message however many products changed', function () {
    // Arrange.
    foreach (range(1, 5) as $id) {
        CatalogueChanged::mark();
    }

    // Act.
    CatalogueChanged::flush();

    // Assert.
    $messages = changedMessages();

    expect($messages)->toHaveCount(1);

    $body = $messages[0]->data();

    expect(array_keys($body))->toBe(['site_id', 'ts', 'sig'])
        ->and($body['site_id'])->toBe($this->siteId)
        ->and($body['sig'])->toBe(hash_hmac('sha256', 'changed:' . $this->siteId . ':' . $body['ts'], $this->feedToken));
});

it('should send nothing when nothing changed', function () {
    // Act.
    CatalogueChanged::flush();

    // Assert.
    expect(changedMessages())->toBeEmpty();
});

it('should send nothing when the store is not connected', function () {
    // Arrange.
    State::forget('feed_token');

    CatalogueChanged::mark();

    // Act.
    CatalogueChanged::flush();

    // Assert.
    expect(changedMessages())->toBeEmpty();
});

it('should send nothing when the merchant switched the feed off', function () {
    // Arrange.
    State::set('feed_enabled', '0');

    CatalogueChanged::mark();

    // Act.
    CatalogueChanged::flush();

    // Assert.
    expect(changedMessages())->toBeEmpty();
});

it('should send at once from the command line, where nobody is waiting', function () {
    // Arrange: a store with a real queue, as under mod_php.
    config(['queue.default' => 'database']);

    Queue::fake();

    CatalogueChanged::mark();

    // Act.
    CatalogueChanged::flush();

    // Assert: an import on the command line sends the message itself.
    expect(app()->runningInConsole())->toBeTrue();

    Queue::assertNothingPushed();

    expect(changedMessages())->toHaveCount(1);
});
