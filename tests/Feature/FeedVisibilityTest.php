<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Repositories\ProductRepository;

beforeEach(function () {
    $this->connectStore();
});

/**
 * Change one attribute the way the admin's mass update does: through the
 * repository, then the event that rewrites product_flat.
 */
function setProductAttribute(int $productId, string $code, $value): void
{
    $repository = app(ProductRepository::class);

    Event::dispatch('catalog.product.update.before', $productId);

    $product = $repository->update([
        'channel' => core()->getDefaultChannelCode(),
        'locale'  => core()->getDefaultLocaleCodeFromDefaultChannel(),
        $code     => $value,
    ], $productId, [$code]);

    Event::dispatch('catalog.product.update.after', $product);
}

it('should list an enabled product that is visible on its own', function () {
    // Arrange.
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    // Act.
    $sent = $this->feedProduct($product->id);

    // Assert.
    expect($sent)->not->toBeNull()
        ->and($sent['sku'])->toBe($product->sku)
        ->and($sent['stock_status'])->toBe('In Stock');
});

it('should leave out a product the merchant disabled', function () {
    // Arrange.
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    expect($this->feedProduct($product->id))->not->toBeNull();

    // Act.
    setProductAttribute($product->id, 'status', 0);

    // Assert.
    expect($this->feedProduct($product->id))->toBeNull();
});

it('should list the product again once it is enabled', function () {
    // Arrange.
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    setProductAttribute($product->id, 'status', 0);

    // Act.
    setProductAttribute($product->id, 'status', 1);

    // Assert.
    expect($this->feedProduct($product->id))->not->toBeNull();
});

it('should leave out a product that is not visible on its own', function () {
    // Arrange.
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    // Act.
    setProductAttribute($product->id, 'visible_individually', 0);

    // Assert.
    expect($this->feedProduct($product->id))->toBeNull();
});

it('should never count a hidden product on a catalogue page', function () {
    // Arrange.
    $shown = (new ProductFaker)->getSimpleProductFactory()->create();

    $hidden = (new ProductFaker)->getSimpleProductFactory()->create();

    setProductAttribute($hidden->id, 'status', 0);

    // Act.
    $ids = collect($this->getFeed(['page' => '1', 'limit' => '250'])->assertOk()->json('products'))
        ->pluck('id')
        ->all();

    // Assert.
    expect($ids)->toContain($shown->id)
        ->and($ids)->not->toContain($hidden->id);
});

it('should leave out a product that only exists in another locale', function () {
    // Arrange.
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    DB::table('product_flat')
        ->where('product_id', $product->id)
        ->update(['locale' => 'xx']);

    // Act and Assert.
    expect($this->feedProduct($product->id))->toBeNull();
});
