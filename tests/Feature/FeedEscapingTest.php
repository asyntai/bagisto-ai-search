<?php

use Illuminate\Support\Facades\DB;
use Webkul\Faker\Helpers\Product as ProductFaker;

beforeEach(function () {
    $this->connectStore();
});

/**
 * Store a name and a description the way product_flat holds them, then read
 * the product back through the signed feed.
 */
function sendThroughFeed($test, string $name, string $description = 'Plain description.'): array
{
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    DB::table('product_flat')
        ->where('product_id', $product->id)
        ->update(['name' => $name, 'description' => $description, 'short_description' => '']);

    return $test->feedProduct($product->id);
}

it('should send a single-escaped name as plain text', function () {
    // Act.
    $sent = sendThroughFeed($this, e('Headphones <script>alert(1)</script>'));

    // Assert.
    expect($sent['name'])->toBe('Headphones');
});

it('should send a double-escaped name as plain text', function () {
    // Act.
    $sent = sendThroughFeed($this, '&amp;lt;b&amp;gt;double&amp;lt;/b&amp;gt;');

    // Assert.
    expect($sent['name'])->toBe('double');
});

it('should send a triple-escaped name as plain text', function () {
    // Act.
    $sent = sendThroughFeed($this, e(e(e('<i>triple</i> escaped'))));

    // Assert.
    expect($sent['name'])->toBe('triple escaped');
});

it('should keep punctuation, accents and emoji in a name', function () {
    // Act.
    $sent = sendThroughFeed($this, e('Over-Ear Headphones "Pro" & <b>Max</b> — Café 50% off ☕'));

    // Assert.
    expect($sent['name'])->toBe('Over-Ear Headphones "Pro" & Max — Café 50% off ☕');
});

it('should keep a less-than sign that is not a tag', function () {
    // Act.
    $sent = sendThroughFeed($this, e("Tom's 5 < 10 cm Mug"));

    // Assert.
    expect($sent['name'])->toBe("Tom's 5 < 10 cm Mug");
});

it('should strip markup from a description', function () {
    // Act.
    $sent = sendThroughFeed($this, 'Lamp', '<p>Warm &amp; bright</p><script>alert(1)</script><p>Second&nbsp;line</p>');

    // Assert.
    expect($sent['description'])->toBe('Warm & bright Second line');
});

it('should send single- and double-escaped markup in a description as plain text', function () {
    // Act.
    $sent = sendThroughFeed($this, 'Lamp', '&lt;p&gt;Escaped &lt;b&gt;once&lt;/b&gt;&lt;/p&gt; and &amp;lt;i&amp;gt;twice&amp;lt;/i&amp;gt;');

    // Assert.
    expect($sent['description'])->toBe('Escaped once and twice');
});

it('should never send a tag in any name or description', function () {
    // Arrange.
    sendThroughFeed($this, '&lt;scr&lt;b&gt;ipt&gt;alert(1)', '&amp;lt;img src=x onerror=alert(1)&amp;gt;');

    // Act.
    $products = $this->getFeed(['page' => '1', 'limit' => '250'])->assertOk()->json('products');

    // Assert.
    foreach ($products as $product) {
        expect($product['name'])->not->toMatch('/<\/?[a-zA-Z!]/');

        if (isset($product['description'])) {
            expect($product['description'])->not->toMatch('/<\/?[a-zA-Z!]/');
        }
    }
});
