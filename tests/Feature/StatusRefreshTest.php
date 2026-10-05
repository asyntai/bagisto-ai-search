<?php

use Asyntai\Search\State;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->connectStore();

    // An answer old enough to be refreshed.
    State::set('status', json_encode(['enabled' => true, 'reason' => 'ok', 'cache_seconds' => 60]));
    State::set('status_at', (string) (time() - 3600));

    Http::fake(['*' => Http::response(['enabled' => true, 'reason' => 'ok', 'cache_seconds' => 600], 200)]);
});

it('should never make a shopper wait when there is no queue and no PHP-FPM', function () {
    // Arrange.
    if (function_exists('fastcgi_finish_request')) {
        $this->markTestSkipped('This PHP has fastcgi_finish_request, so the shopper never waits anyway.');
    }

    config(['queue.default' => 'sync']);

    $stamp = State::get('status_at');

    // Act.
    State::refreshFromSite();

    // Assert: no call, and the answer is still stale for the scheduler.
    Http::assertNothingSent();

    expect(State::get('status_at'))->toBe($stamp);
});

it('should hand the refresh to the queue when the store has one', function () {
    // Arrange.
    if (function_exists('fastcgi_finish_request')) {
        $this->markTestSkipped('This PHP has fastcgi_finish_request, so the refresh runs after the response.');
    }

    config(['queue.default' => 'database']);

    Queue::fake();

    // Act.
    State::refreshFromSite();

    // Assert: queued, not called, and claimed so the next page does not queue it again.
    Queue::assertPushed(\Illuminate\Queue\CallQueuedClosure::class, 1);

    Http::assertNothingSent();

    expect((int) State::get('status_at'))->toBeGreaterThan(time() - 5);

    State::refreshFromSite();

    Queue::assertPushed(\Illuminate\Queue\CallQueuedClosure::class, 1);
});

it('should not refresh an answer that is still fresh', function () {
    // Arrange.
    config(['queue.default' => 'database']);

    Queue::fake();

    State::set('status_at', (string) time());

    // Act.
    State::refreshFromSite();

    // Assert.
    Queue::assertNothingPushed();

    Http::assertNothingSent();
});

it('should refresh at once from the scheduler', function () {
    // Act.
    $status = State::refreshIfStale();

    // Assert.
    expect($status)->toBeArray()
        ->and($status['enabled'])->toBeTrue();

    Http::assertSentCount(1);
});
